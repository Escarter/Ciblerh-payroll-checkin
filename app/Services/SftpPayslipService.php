<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Company;
use Escarter\PopplerPhp\PdfToText;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemOperationFailed;

class SftpPayslipService
{
    /**
     * SFTP configuration from settings
     */
    private array $config;
    
    /**
     * Cached SFTP disk instance
     */
    private $disk = null;

    public function __construct()
    {
        $setting = \App\Models\Setting::first();
        $this->config = [
            'host' => $setting->sftp_host ?? null,
            'port' => $setting->sftp_port ?? 22,
            'username' => $setting->sftp_username ?? null,
            'password' => $setting->sftp_auth_type === 'password' ? ($setting->sftp_password ?? null) : null,
            'privateKey' => $setting->sftp_auth_type === 'ssh_key' ? ($setting->sftp_private_key_path ?? null) : null,
            'passphrase' => $setting->sftp_auth_type === 'ssh_key' ? ($setting->sftp_passphrase ?? null) : null,
            'root' => $setting->sftp_root ?? '/payslips',
            'timeout' => 30,
            'auth_type' => $setting->sftp_auth_type ?? 'password',
        ];
    }

    /**
     * Get or create SFTP disk connection (connection pooling)
     *
     * @return mixed
     */
    private function getDisk()
    {
        if ($this->disk === null) {
            $config = [
                'host' => $this->config['host'],
                'username' => $this->config['username'],
                'port' => $this->config['port'],
                'timeout' => $this->config['timeout'],
            ];

            // Configure based on auth type
            if ($this->config['auth_type'] === 'ssh_key') {
                $config['privateKey'] = $this->config['privateKey'];
                if ($this->config['passphrase']) {
                    $config['passphrase'] = $this->config['passphrase'];
                }
            } else {
                $config['password'] = $this->config['password'];
            }

            $this->disk = Storage::build([
                'driver' => 'sftp',
                ...$config,
                'root' => $this->config['root'],
            ]);
        }

        return $this->disk;
    }

    /**
     * Fetch payslips from SFTP server with advanced filtering
     *
     * @param array $options Options for filtering:
     *   - lastModifiedAfter: int (timestamp) - only files modified after this time
     *   - maxDepth: int - limit directory depth (default: unlimited)
     *   - limit: int - max files to return per batch (default: 100)
     *   - offset: int - pagination offset (default: 0)
     *   - extensions: array - file extensions to include (default: ['pdf'])
     *   - returnDirs: bool - include directories in response (default: false)
     * @return array
     */
    public function fetchPayslipsFromSftp(array $options = []): array
    {
        try {
            // Set defaults
            $lastModifiedAfter = $options['lastModifiedAfter'] ?? 0;
            $maxDepth = $options['maxDepth'] ?? null;
            $limit = $options['limit'] ?? 100;
            $offset = $options['offset'] ?? 0;
            $extensions = $options['extensions'] ?? ['pdf'];
            $returnDirs = $options['returnDirs'] ?? false;

            $disk = $this->getDisk();
            
            $files = [];
            $skipped = 0;
            $processedCount = 0;

            try {
                $contents = $disk->listContents('/', true);
            } catch (FilesystemOperationFailed $e) {
                return [
                    'success' => false,
                    'error' => "Failed to list SFTP contents: {$e->getMessage()}",
                    'files' => [],
                    'count' => 0,
                    'skipped' => 0,
                ];
            }

            foreach ($contents as $item) {
                // Respect max depth setting
                if ($maxDepth !== null) {
                    $depth = substr_count($item->path(), '/');
                    if ($depth > $maxDepth) {
                        continue;
                    }
                }

                // Handle directories if requested
                if ($item->isDir()) {
                    if ($returnDirs) {
                        $files[] = [
                            'type' => 'dir',
                            'path' => $item->path(),
                            'basename' => basename($item->path()),
                        ];
                    }
                    continue;
                }

                // Filter by file extension
                $filename = strtolower($item->filename());
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (!in_array($ext, $extensions)) {
                    $skipped++;
                    continue;
                }

                // Filter by lastModified timestamp
                $fileTimestamp = $item->lastModified();
                if ($fileTimestamp < $lastModifiedAfter) {
                    $skipped++;
                    continue;
                }

                $processedCount++;

                // Apply pagination
                if ($processedCount <= $offset) {
                    continue;
                }

                if (count($files) >= $limit) {
                    break;
                }

                // Safely extract file metadata with error handling
                try {
                    $files[] = [
                        'type' => 'file',
                        'path' => $item->path(),
                        'filename' => $item->filename(),
                        'basename' => basename($item->path()),
                        'size' => $item->fileSize(),
                        'timestamp' => $fileTimestamp,
                        'mimetype' => $item->mimeType() ?? 'application/octet-stream',
                    ];
                } catch (\Exception $e) {
                    \Log::warning("Error extracting metadata for {$item->path()}: {$e->getMessage()}");
                    continue;
                }
            }

            return [
                'success' => true,
                'files' => $files,
                'count' => count($files),
                'skipped' => $skipped,
                'processed' => $processedCount,
                'hasMore' => $processedCount > ($offset + $limit),
                'offset' => $offset,
                'limit' => $limit,
            ];
        } catch (\Exception $e) {
            \Log::error("SFTP fetch error: {$e->getMessage()}", [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'files' => [],
                'count' => 0,
                'skipped' => 0,
            ];
        }
    }

    /**
     * Legacy method for backward compatibility - fetches all PDFs
     *
     * @return array
     */
    public function fetchPayslipsFromSftpLegacy(): array
    {
        return $this->fetchPayslipsFromSftp([
            'extensions' => ['pdf'],
            'limit' => 1000, // Fetch up to 1000 files
        ]);
    }

    /**
     * Parse filename and extract metadata using matching strategies
     *
     * @param string $filename
     * @param string $filepath
     * @param int $timestamp
     * @param array $strategies
     * @return array
     */
    public function parsePayslipMetadata(string $filename, string $filepath, int $timestamp, array $strategies): array
    {
        $metadata = [
            'filename' => $filename,
            'filepath' => $filepath,
            'timestamp' => $timestamp,
            'matches' => [],
        ];

        // Remove extension
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Try each enabled strategy
        if (in_array('employee_id', $strategies)) {
            $this->extractEmployeeId($name, $metadata);
        }

        if (in_array('department_code', $strategies)) {
            $this->extractDepartmentCode($name, $metadata);
        }

        if (in_array('company_code', $strategies)) {
            $this->extractCompanyCode($name, $metadata);
        }

        if (in_array('folder_structure', $strategies)) {
            $this->extractFromFolderStructure($filepath, $metadata);
        }

        if (in_array('fuzzy', $strategies)) {
            $this->extractFromTimestamp($timestamp, $metadata);
        }

        return $metadata;
    }

    /**
     * Match payslip to entities with confidence scores
     *
     * @param array $metadata
     * @return array
     */
    public function matchPayslipToEntities(array $metadata): array
    {
        $candidates = [];

        // Search by employee_id if found
        if (!empty($metadata['matches']['employee_id'])) {
            $employee = \App\Models\User::find($metadata['matches']['employee_id'])->first();
            if ($employee) {
                $candidates[] = [
                    'type' => 'employee',
                    'strategy' => 'employee_id',
                    'confidence' => 0.95,
                    'employee_id' => $employee->id,
                    'department_id' => $employee->department_id,
                    'company_id' => $employee->company_id,
                    'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                    'department_name' => $employee->department?->name,
                    'company_name' => $employee->company?->name,
                ];
            }
        }

        // Search by department code if found
        if (!empty($metadata['matches']['department_code'])) {
            $department = Department::where('code', $metadata['matches']['department_code'])
                ->orWhere('name', 'ilike', '%' . $metadata['matches']['department_code'] . '%')
                ->first();
            
            if ($department) {
                $candidates[] = [
                    'type' => 'department',
                    'strategy' => 'department_code',
                    'confidence' => 0.85,
                    'department_id' => $department->id,
                    'company_id' => $department->company_id,
                    'department_name' => $department->name,
                    'company_name' => $department->company?->name,
                ];
            }
        }

        // Search by company code if found
        if (!empty($metadata['matches']['company_code'])) {
            $company = Company::where('code', $metadata['matches']['company_code'])
                ->orWhere('name', 'ilike', '%' . $metadata['matches']['company_code'] . '%')
                ->first();
            
            if ($company) {
                $candidates[] = [
                    'type' => 'company',
                    'strategy' => 'company_code',
                    'confidence' => 0.75,
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                ];
            }
        }

        // Search by folder structure if found
        if (!empty($metadata['matches']['folder_department'])) {
            $department = Department::where('code', $metadata['matches']['folder_department'])
                ->orWhere('name', 'ilike', '%' . $metadata['matches']['folder_department'] . '%')
                ->first();
            
            if ($department) {
                $candidates[] = [
                    'type' => 'folder_department',
                    'strategy' => 'folder_structure',
                    'confidence' => 0.80,
                    'department_id' => $department->id,
                    'company_id' => $department->company_id,
                    'department_name' => $department->name,
                    'company_name' => $department->company?->name,
                ];
            }
        }

        // Sort by confidence score
        usort($candidates, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        return [
            'candidates' => $candidates,
            'best_match' => !empty($candidates) ? $candidates[0] : null,
        ];
    }

    /**
     * Download payslip from SFTP
     *
     * @param string $remotePath
     * @param string $localPath
     * @return bool
     */
    public function downloadPayslip(string $remotePath, string $localPath): ?string
    {
        try {
            $config = [
                'host' => $this->config['host'],
                'username' => $this->config['username'],
                'port' => $this->config['port'],
                'timeout' => $this->config['timeout'],
            ];

            if ($this->config['auth_type'] === 'ssh_key') {
                $config['privateKey'] = $this->config['privateKey'];
                if ($this->config['passphrase']) {
                    $config['passphrase'] = $this->config['passphrase'];
                }
            } else {
                $config['password'] = $this->config['password'];
            }

            $disk = Storage::build([
                'driver' => 'sftp',
                ...$config,
                'root' => $this->config['root'],
            ]);

            $content = $disk->get($remotePath);
            Storage::disk('local')->put($localPath, $content);

            return $localPath;
        } catch (\Exception $e) {
            \Log::error("Failed to download payslip from SFTP: {$remotePath} - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Test SFTP connection
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            $config = [
                'host' => $this->config['host'],
                'username' => $this->config['username'],
                'port' => $this->config['port'],
                'timeout' => 10,
            ];

            if ($this->config['auth_type'] === 'ssh_key') {
                $config['privateKey'] = $this->config['privateKey'];
                if ($this->config['passphrase']) {
                    $config['passphrase'] = $this->config['passphrase'];
                }
            } else {
                $config['password'] = $this->config['password'];
            }

            $disk = Storage::build([
                'driver' => 'sftp',
                ...$config,
                'root' => $this->config['root'],
            ]);

            // Try to access the root directory to test connection
            $disk->exists('/');

            return [
                'success' => true,
                'message' => 'SFTP connection established successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'SFTP connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Extract employee ID from filename
     */
    private function extractEmployeeId(string $name, array &$metadata): void
    {
        // Pattern: EMP_12345 or E12345 or employee_id_12345
        if (preg_match('/(?:emp|employee|e)[\s_-]?(\d+)/i', $name, $matches)) {
            $metadata['matches']['employee_id'] = (int)$matches[1];
        }
    }

    /**
     * Extract department code from filename
     */
    private function extractDepartmentCode(string $name, array &$metadata): void
    {
        // Pattern: DEPT_ABC or D_ABC or dept_code_ABC
        if (preg_match('/(?:dept|department|d)[\s_-]([A-Z0-9]+)/i', $name, $matches)) {
            $metadata['matches']['department_code'] = $matches[1];
        }
    }

    /**
     * Extract company code from filename
     */
    private function extractCompanyCode(string $name, array &$metadata): void
    {
        // Pattern: COMP_XYZ or C_XYZ or company_XYZ
        if (preg_match('/(?:comp|company|c)[\s_-]([A-Z0-9]+)/i', $name, $matches)) {
            $metadata['matches']['company_code'] = $matches[1];
        }
    }

    /**
     * Extract department from folder structure
     */
    private function extractFromFolderStructure(string $filepath, array &$metadata): void
    {
        // Pattern: /payslips/DEPT_CODE/file.pdf or /payslips/HR/file.pdf
        $parts = explode('/', trim($filepath, '/'));
        
        if (count($parts) >= 2) {
            // Second part is usually the department
            $metadata['matches']['folder_department'] = $parts[1];
        }
    }

    /**
     * Extract month/year from timestamp and filename
     */
    private function extractFromTimestamp(int $timestamp, array &$metadata): void
    {
        $date = \Carbon\Carbon::createFromTimestamp($timestamp);
        $metadata['matches']['timestamp_month'] = $date->month;
        $metadata['matches']['timestamp_year'] = $date->year;
    }

    /**
     * Extract company name and pay period from a pushed PDF file.
     *
     * Company name: reads the first non-empty lines at the top of the document
     * before any employee-specific field appears (Matricule, Nom, CIN, etc.).
     *
     * Period: searches for "Période du DD/MM/YYYY" (or "Periode du …") anywhere
     * in the text and derives month + year from the start date.
     *
     * @param  string $absoluteFilePath Absolute filesystem path to the PDF
     * @return array{company_raw: string|null, month: int|null, year: int|null, raw_text_preview: string}
     */
    public function extractPdfMetadata(string $absoluteFilePath): array
    {
        $result = [
            'company_raw'       => null,
            'month'             => null,
            'year'              => null,
            'raw_text_preview'  => '',
        ];

        try {
            $text = PdfToText::getText($absoluteFilePath, config('ciblerh.pdftotext_path'));
        } catch (\Throwable $e) {
            \Log::warning('SftpPayslipService: PdfToText failed', [
                'file' => $absoluteFilePath,
                'error' => $e->getMessage(),
            ]);
            return $result;
        }

        if (empty(trim($text))) {
            return $result;
        }

        $result['raw_text_preview'] = substr($text, 0, 500);

        // ── 1. Extract company name ──────────────────────────────────────────
        // Employee-specific keywords that signal the header block has ended.
        $stopKeywords = ['matricule', 'nom', 'cin', 'employé', 'employe', 'département', 'departement',
                         'service', 'bulletin', 'fiche', 'salaire', 'brut', 'net', 'date'];

        $lines = preg_split('/\r?\n/', $text);
        $companyCandidate = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) {
                continue;
            }

            // Stop as soon as we reach an employee-specific field
            $lower = mb_strtolower($trimmed);
            $isStop = false;
            foreach ($stopKeywords as $kw) {
                if (str_contains($lower, $kw)) {
                    $isStop = true;
                    break;
                }
            }
            if ($isStop) {
                break;
            }

            // Take the first meaningful line as company name candidate
            // Skip lines that look like page numbers or pure numbers
            if (!$companyCandidate && !preg_match('/^\d+$/', $trimmed)) {
                $companyCandidate = $trimmed;
            }
        }

        $result['company_raw'] = $companyCandidate;

        // ── 2. Extract pay period ────────────────────────────────────────────
        // Helper: normalise 2-digit years to 4-digit (25 → 2025, 99 → 1999).
        $normalizeYear = static function (int $y): int {
            return $y < 100 ? ($y >= 50 ? 1900 + $y : 2000 + $y) : $y;
        };

        // French and English month name → integer map (accent-normalised)
        $monthNames = [
            'janvier'   => 1,  'fevrier'  => 2,  'février'  => 2,
            'mars'      => 3,  'avril'    => 4,  'mai'      => 5,
            'juin'      => 6,  'juillet'  => 7,  'aout'     => 8,  'août' => 8,
            'septembre' => 9,  'octobre'  => 10, 'novembre' => 11,
            'decembre'  => 12, 'décembre' => 12,
            'january'   => 1,  'february' => 2,  'march'    => 3,
            'april'     => 4,  'may'      => 5,  'june'     => 6,
            'july'      => 7,  'august'   => 8,  'september'=> 9,
            'october'   => 10, 'november' => 11, 'december' => 12,
        ];

        // Pass 1: "Période du DD/MM/YY[YY]" — explicit label (2- or 4-digit year)
        //   Real layout: due to columnar PDF extraction the date may appear on a
        //   separate line from "Période du", so allow [\s\S]{0,80} between them.
        if (!$result['month'] && preg_match(
            '/p[ée]riode\s+du[\s\S]{0,80}?(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})/iu',
            $text, $m
        )) {
            $result['month'] = (int) $m[2];
            $result['year']  = $normalizeYear((int) $m[3]);
        }

        // Pass 2: "au DD/MM/YY[YY]" — end-of-period marker (appears alone in the
        //   extracted text before "Période du" due to columnar layout).
        //   e.g. "au 31/12/25"
        if (!$result['month'] && preg_match(
            '/\bau\s+(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})\b/iu',
            $text, $m
        )) {
            $result['month'] = (int) $m[2];
            $result['year']  = $normalizeYear((int) $m[3]);
        }

        // Pass 3: "Du DD/MM/YY[YY] au DD/MM/YY[YY]" — full date range on one line
        if (!$result['month'] && preg_match(
            '/\bdu\s+\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4}\s+au\s+\d{1,2}[\/\-.](\d{1,2})[\/\-.](\d{2,4})/iu',
            $text, $m
        )) {
            $result['month'] = (int) $m[1];
            $result['year']  = $normalizeYear((int) $m[2]);
        }

        // Pass 4: French/English month name followed by 2- or 4-digit year
        //   e.g. "JANVIER 2025", "Mois : Janvier 25", "Bulletin de Paie Mars 2025"
        if (!$result['month'] && preg_match(
            '/\b(janvier|f[ée]vrier|mars|avril|mai|juin|juillet|ao[uû]t|septembre|octobre|novembre|d[ée]cembre'
            . '|january|february|march|april|may|june|july|august|september|october|november|december)'
            . '\b[^0-9]{0,25}(\d{2,4})\b/iu',
            $text, $m
        )) {
            $key = mb_strtolower(trim($m[1]));
            $yr  = $normalizeYear((int) $m[2]);
            if (isset($monthNames[$key]) && $yr >= 2000) {
                $result['month'] = $monthNames[$key];
                $result['year']  = $yr;
            }
        }

        // Pass 5: Filename patterns (basename may encode the period)
        //   YYYY-MM / YYYY_MM, MM-YYYY / MM_YYYY, YY-MM / YY_MM, French month + year
        if (!$result['month']) {
            $basename = pathinfo($absoluteFilePath, PATHINFO_FILENAME);

            if (preg_match('/\b(20\d{2})[-_](0[1-9]|1[0-2])\b/', $basename, $m)) {
                $result['year']  = (int) $m[1];
                $result['month'] = (int) $m[2];
            } elseif (preg_match('/\b(0[1-9]|1[0-2])[-_](20\d{2})\b/', $basename, $m)) {
                $result['month'] = (int) $m[1];
                $result['year']  = (int) $m[2];
            } elseif (preg_match('/\b(\d{2})[-_](0[1-9]|1[0-2])\b/', $basename, $m)) {
                $result['year']  = $normalizeYear((int) $m[1]);
                $result['month'] = (int) $m[2];
            } elseif (preg_match('/\b(0[1-9]|1[0-2])[-_](\d{2})\b/', $basename, $m)) {
                $result['month'] = (int) $m[1];
                $result['year']  = $normalizeYear((int) $m[2]);
            } elseif (preg_match(
                '/\b(janvier|f[ée]vrier|mars|avril|mai|juin|juillet|ao[uû]t|septembre|octobre|novembre|d[ée]cembre)'
                . '[^0-9]{0,15}(\d{2,4})\b/iu',
                $basename, $m
            )) {
                $key = mb_strtolower(trim($m[1]));
                $yr  = $normalizeYear((int) $m[2]);
                if ($yr >= 2000) {
                    $result['month'] = $monthNames[$key] ?? null;
                    $result['year']  = $yr;
                }
            }
        }

        // Pass 6: Standalone DD/MM/YY[YY] date — look for a day-31 or day-28/30 end-of-month
        //   date which strongly signals the payslip period. e.g. "01/12/25"
        //   Require day 01 (period start) or 28-31 (period end) to reduce false positives.
        if (!$result['month'] && preg_match(
            '/\b(0[1-9]|[12]\d|3[01])[\/\-](0[1-9]|1[0-2])[\/\-](\d{2,4})\b/',
            $text, $m
        )) {
            $yr = $normalizeYear((int) $m[3]);
            if ($yr >= 2000) {
                $result['month'] = (int) $m[2];
                $result['year']  = $yr;
            }
        }

        return $result;
    }

    /**
     * Fuzzy-match a raw company name string against all active companies.
     *
     * Strategy:
     *  1. SQL LIKE partial match (confidence 0.90)
     *  2. PHP similar_text() scoring across all companies (min 50% similarity)
     *
     * @param  string $rawName
     * @return array  Sorted candidates: [['company_id', 'company_name', 'confidence', 'strategy'], …]
     */
    public function matchCompanyFuzzy(string $rawName): array
    {
        if (empty(trim($rawName))) {
            return [];
        }

        $candidates = [];
        $seen       = [];
        $rawLower   = mb_strtolower(trim($rawName));

        $allCompanies = Company::where('is_active', true)->get();

        foreach ($allCompanies as $company) {
            $companyLower = mb_strtolower($company->name);

            // Pass 1a: company name contains the full raw text
            // (e.g. raw = "PERENCO", company = "PERENCO CAMEROUN")
            if (str_contains($companyLower, $rawLower)) {
                $seen[$company->id] = true;
                $candidates[] = [
                    'company_id'   => $company->id,
                    'company_name' => $company->name,
                    'confidence'   => 0.90,
                    'strategy'     => 'partial_match',
                ];
                continue;
            }

            // Pass 1b: raw text contains the company name as a whole word/phrase
            // (e.g. raw = "CIBLE RH MISE A DISPOSITION PERENCO WORK OVER", company = "PERENCO")
            if (preg_match('/\b' . preg_quote($companyLower, '/') . '\b/iu', $rawLower)) {
                $seen[$company->id] = true;
                $candidates[] = [
                    'company_id'   => $company->id,
                    'company_name' => $company->name,
                    'confidence'   => 0.85,
                    'strategy'     => 'reverse_partial_match',
                ];
                continue;
            }

            // Pass 2: fuzzy similarity — handles typos and short names not caught above
            // Compare company name against raw text for a fair length-normalised score
            similar_text($companyLower, $rawLower, $percent);

            // Also test similarity against individual words from raw text
            $words = preg_split('/\s+/', $rawLower);
            foreach ($words as $word) {
                if (strlen($word) < 3) continue;
                similar_text($companyLower, $word, $wordPercent);
                if ($wordPercent > $percent) {
                    $percent = $wordPercent;
                }
            }

            if ($percent >= 50.0) {
                $candidates[] = [
                    'company_id'   => $company->id,
                    'company_name' => $company->name,
                    'confidence'   => round($percent / 100, 2),
                    'strategy'     => 'fuzzy',
                ];
            }
        }

        // Sort by confidence descending
        usort($candidates, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        return $candidates;
    }
}
