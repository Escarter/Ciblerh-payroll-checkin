<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class SftpPayslipService
{
    /**
     * SFTP configuration from settings
     */
    private array $config;

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
     * Fetch payslips from SFTP server
     *
     * @return array
     */
    public function fetchPayslipsFromSftp(): array
    {
        try {
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

            $disk = Storage::build([
                'driver' => 'sftp',
                ...$config,
                'root' => $this->config['root'],
            ]);

            $files = [];
            $contents = $disk->listContents('/', true);

            foreach ($contents as $item) {
                if ($item->isFile()) {
                    $files[] = [
                        'path' => $item->path(),
                        'filename' => $item->filename(),
                        'basename' => basename($item->path()),
                        'size' => $item->fileSize(),
                        'timestamp' => $item->lastModified(),
                        'mimetype' => $item->mimeType(),
                    ];
                }
            }

            return [
                'success' => true,
                'files' => $files,
                'count' => count($files),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'files' => [],
                'count' => 0,
            ];
        }
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

            // Try to list root directory
            $disk->listContents('/', false);

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
}
