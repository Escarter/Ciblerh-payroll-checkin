<?php

namespace App\Console\Commands;

use App\Models\Payslip;
use App\Models\SendPayslipProcess;
use App\Models\User;
use App\Services\PayslipProcessLinkService;
use Escarter\PopplerPhp\PdfToText;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class DiagnosePayslipPdfCommand extends Command
{
    protected $signature = 'payslips:diagnose
                            {--process= : SendPayslipProcess ID}
                            {--directory= : Splitted destination_directory (e.g. oBeE3kgvyHygl2sjSoKy)}
                            {--pdf= : Absolute path to a single PDF page}
                            {--matricule= : Matricule to test token matching against --pdf}
                            {--sample-pages=0 : Max splitted pages to scan when comparing pool (0 = all)}';

    protected $description = 'Diagnose PDF tools, matricule extraction, and employee-pool matching for payslip sending';

    public function handle(): int
    {
        $hasFailure = false;

        $this->info('=== PDF tool paths ===');
        $hasFailure = $this->checkPdfTools() || $hasFailure;

        $pdfPath = $this->option('pdf');
        $matricule = $this->option('matricule');

        if ($pdfPath) {
            $this->newLine();
            $this->info('=== Single PDF test ===');
            $hasFailure = $this->diagnoseSinglePdf($pdfPath, $matricule) || $hasFailure;
        }

        $process = $this->resolveProcess();
        if ($process) {
            $this->newLine();
            $this->info("=== Process #{$process->id} ({$process->month}/{$process->year}) ===");
            $hasFailure = $this->diagnoseProcess($process) || $hasFailure;
        } elseif ($this->option('process') || $this->option('directory')) {
            $this->error('Send payslip process not found for the given --process or --directory.');

            return self::FAILURE;
        }

        if ($hasFailure) {
            $this->newLine();
            $this->warn('One or more checks failed. Review the table output above.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('All payslip PDF diagnostics checks passed.');

        return self::SUCCESS;
    }

    private function resolveProcess(): ?SendPayslipProcess
    {
        if ($id = $this->option('process')) {
            return SendPayslipProcess::find($id);
        }

        if ($dir = $this->option('directory')) {
            return SendPayslipProcess::where('destination_directory', $dir)->first();
        }

        return null;
    }

    private function checkPdfTools(): bool
    {
        $tools = [
            'pdftotext' => config('ciblerh.pdftotext_path'),
            'pdfseparate' => config('ciblerh.pdftsepare_path'),
            'pdftk' => config('ciblerh.pdftk_path'),
        ];

        $rows = [];
        $failed = false;

        foreach ($tools as $label => $path) {
            if (empty($path)) {
                $rows[] = [$label, 'MISSING', 'Config path is empty'];
                $failed = true;
                continue;
            }

            if (!is_executable($path)) {
                $rows[] = [$label, 'MISSING', "Not executable: {$path}"];
                $failed = true;
                continue;
            }

            $process = new Process([$path, '-v']);
            $process->run();
            $version = trim($process->getErrorOutput() ?: $process->getOutput());
            $version = $version !== '' ? strtok($version, "\n") : 'unknown version';

            $rows[] = [$label, 'OK', "{$path} ({$version})"];
        }

        $this->table(['Tool', 'Status', 'Detail'], $rows);

        return $failed;
    }

    private function diagnoseSinglePdf(string $pdfPath, ?string $matricule): bool
    {
        if (!is_readable($pdfPath)) {
            $this->error("Cannot read PDF: {$pdfPath}");

            return true;
        }

        try {
            $text = PdfToText::getText($pdfPath, config('ciblerh.pdftotext_path'));
        } catch (\Throwable $e) {
            $this->error('pdftotext failed: ' . $e->getMessage());

            return true;
        }

        $matricules = $this->extractMatriculesFromText($text);
        $utf8Ok = mb_check_encoding($text, 'UTF-8') ? 'yes' : 'no (sanitized for matching)';

        $this->line("Path: {$pdfPath}");
        $this->line('Extracted text length: ' . strlen($text));
        $this->line('UTF-8 valid: ' . $utf8Ok);
        $this->line('Matricules found: ' . count($matricules));
        if ($matricules !== []) {
            $this->line('Sample: ' . implode(', ', array_slice($matricules, 0, 10)));
        }

        if ($matricule) {
            $match = User::matriculeTokenExistsInPdfText($text, $matricule);
            $this->line("Token match for {$matricule}: " . ($match ? 'MATCH' : 'NO MATCH'));

            return !$match;
        }

        return strlen($text) === 0;
    }

    private function diagnoseProcess(SendPayslipProcess $process): bool
    {
        $failed = false;

        $this->line("Company: {$process->company_id} | Department: " . ($process->department_id ?? 'n/a'));
        $this->line("Status: {$process->status} | Directory: {$process->destination_directory}");
        if ($process->failure_reason) {
            $this->line('Failure: ' . substr($process->failure_reason, 0, 160));
        }

        $linkService = app(PayslipProcessLinkService::class);
        $employees = $linkService->resolveEmployeePool($process);
        $this->line('Employee pool size: ' . $employees->count());

        $failedOnProcess = Payslip::query()
            ->where('send_payslip_process_id', $process->id)
            ->where('encryption_status', Payslip::STATUS_FAILED)
            ->where(function ($query) {
                $query->whereNull('file')->orWhere('file', '');
            })
            ->count();

        $withFileOnProcess = Payslip::query()
            ->where('send_payslip_process_id', $process->id)
            ->whereNotNull('file')
            ->where('file', '!=', '')
            ->count();

        $orphans = $linkService->findOrphanedFilePayslips($process, $employees);

        $this->newLine();
        $this->info('=== Database (this process) ===');
        $this->table(['Metric', 'Count'], [
            ['Failed payslip rows (no file)', $failedOnProcess],
            ['Payslip rows with file on this process', $withFileOnProcess],
            ['Payslip rows with file on OTHER process (same month/year)', $orphans->count()],
        ]);

        if ($orphans->isNotEmpty()) {
            $this->error('Cross-process mismatch: PDF matching likely updated an older process. Run: php artisan payslips:relink-process --process=' . $process->id);
            foreach ($orphans->take(3) as $row) {
                $this->line("  {$row->matricule} → process #{$row->send_payslip_process_id} (" . basename($row->file) . ')');
            }
            $failed = true;
        }

        if ($failedOnProcess > 0 && $withFileOnProcess === 0 && $orphans->isNotEmpty()) {
            $this->error('UI "Unmatched" tab matches these failed DB rows, but matched files exist on another process.');
            $failed = true;
        }

        $failed = $this->diagnoseFilesOnDiskVsDatabase($process, $employees) || $failed;

        $splittedDir = $process->destination_directory;
        $files = collect(Storage::disk('splitted')->allFiles($splittedDir))
            ->filter(fn (string $file) => str_ends_with(strtolower($file), '.pdf'))
            ->values();

        $modifiedCount = collect(Storage::disk('modified')->allFiles($splittedDir))
            ->filter(fn (string $file) => str_ends_with(strtolower($file), '.pdf'))
            ->count();

        $this->line("Splitted pages: {$files->count()} | Modified PDFs on disk: {$modifiedCount}");

        if ($modifiedCount > 0 && $withFileOnProcess === 0 && $failedOnProcess > 0) {
            $this->warn('Modified PDFs exist on disk but no payslip rows on this process have files — likely a re-run linking bug.');
            $failed = true;
        }

        if ($files->isEmpty()) {
            $this->warn('No splitted PDF pages found for this process directory.');
            $failed = true;
        } else {
            $sampleLimit = max(0, (int) $this->option('sample-pages'));
            $filesToScan = $sampleLimit === 0 ? $files : $files->take($sampleLimit);

            if ($sampleLimit > 0 && $files->count() > $sampleLimit) {
                $this->warn("Only scanning {$sampleLimit} of {$files->count()} pages — use --sample-pages=0 for full scan.");
            }

            $this->newLine();
            $this->info('=== PDF text vs employee pool ===');

            $pdfMatricules = collect();
            foreach ($filesToScan as $file) {
                $path = Storage::disk('splitted')->path($file);
                try {
                    $text = PdfToText::getText($path, config('ciblerh.pdftotext_path'));
                    $pdfMatricules = $pdfMatricules->merge($this->extractMatriculesFromText($text));
                } catch (\Throwable $e) {
                    $this->warn("Failed to read {$file}: " . $e->getMessage());
                    $failed = true;
                }
            }

            $pdfMatricules = $pdfMatricules->map(fn (string $m) => User::normalizeMatricule($m))
                ->filter()
                ->unique()
                ->values();

            $dbMatricules = $employees->pluck('matricule')
                ->map(fn (?string $m) => User::normalizeMatricule($m))
                ->filter()
                ->unique();

            $inBoth = $dbMatricules->intersect($pdfMatricules);
            $inPdfOnly = $pdfMatricules->diff($dbMatricules);
            $inDbOnly = $dbMatricules->diff($pdfMatricules);

            $this->table(['Metric', 'Count'], [
                ['Pages scanned', $filesToScan->count()],
                ['Unique matricules in PDF text', $pdfMatricules->count()],
                ['Employees with matricule in pool', $dbMatricules->count()],
                ['Matched (in PDF and pool)', $inBoth->count()],
                ['In PDF only (not in employee pool)', $inPdfOnly->count()],
                ['In pool only (not in scanned PDF pages)', $inDbOnly->count()],
            ]);

            if ($inPdfOnly->isNotEmpty()) {
                $this->line('PDF only (sample): ' . $inPdfOnly->take(10)->implode(', '));
            }
            if ($inDbOnly->isNotEmpty()) {
                $this->line('Pool only (sample): ' . $inDbOnly->take(10)->implode(', '));
            }

            if ($pdfMatricules->isEmpty() && $filesToScan->isNotEmpty()) {
                $this->error('PDF text contains no matricules — file may be scanned/image-only or corrupt.');
                $failed = true;
            }

            if ($inBoth->isEmpty() && $dbMatricules->isNotEmpty() && $pdfMatricules->isNotEmpty()) {
                $this->error('Zero overlap between PDF matricules and employee pool — wrong company/department or PDF.');
                $failed = true;
            }

            $poolOnlyRatio = $dbMatricules->count() > 0
                ? $inDbOnly->count() / $dbMatricules->count()
                : 0;

            if ($poolOnlyRatio >= 0.5 && $filesToScan->count() < $files->count()) {
                $this->warn('Many employees not in scanned pages — re-run with --sample-pages=0 before concluding matching failed.');
            } elseif ($poolOnlyRatio >= 0.5 && $filesToScan->count() === $files->count()) {
                $this->error('More than half of the employee pool has no matricule in the PDF — true matching failure or wrong PDF.');
                $failed = true;
            }
        }

        return $failed;
    }

    /**
     * Verify payslip file paths in the database exist on the modified disk, and
     * report PDFs present on disk that are not referenced by any payslip row.
     */
    private function diagnoseFilesOnDiskVsDatabase(SendPayslipProcess $process, Collection $employees): bool
    {
        $failed = false;
        $dir = $process->destination_directory;

        $this->newLine();
        $this->info('=== Files on disk vs database ===');

        $payslipsWithFile = Payslip::query()
            ->where('send_payslip_process_id', $process->id)
            ->whereNotNull('file')
            ->where('file', '!=', '')
            ->get(['id', 'matricule', 'file', 'encryption_status']);

        $dbPathsExist = 0;
        $dbPathsMissing = 0;
        $missingSamples = [];

        foreach ($payslipsWithFile as $payslip) {
            if (Storage::disk('modified')->exists($payslip->file)) {
                $dbPathsExist++;
            } else {
                $dbPathsMissing++;
                if (count($missingSamples) < 5) {
                    $missingSamples[] = [
                        $payslip->matricule,
                        basename($payslip->file),
                        'MISSING',
                    ];
                }
            }
        }

        $diskFiles = collect(Storage::disk('modified')->allFiles($dir))
            ->filter(fn (string $file) => str_ends_with(strtolower($file), '.pdf'))
            ->values();

        $referencedPaths = $payslipsWithFile->pluck('file')
            ->merge(
                Payslip::query()
                    ->whereIn('employee_id', $employees->pluck('id'))
                    ->where('month', $process->month)
                    ->where('year', $process->year ?? now()->year)
                    ->whereNotNull('file')
                    ->where('file', '!=', '')
                    ->pluck('file')
            )
            ->unique()
            ->filter()
            ->values();

        $unreferencedOnDisk = $diskFiles->filter(
            fn (string $path) => !$referencedPaths->contains($path)
        );

        $this->table(['Metric', 'Count'], [
            ['Payslip rows with file path (this process)', $payslipsWithFile->count()],
            ['DB file paths that exist on modified disk', $dbPathsExist],
            ['DB file paths MISSING on modified disk', $dbPathsMissing],
            ['PDF files on disk in process folder', $diskFiles->count()],
            ['Disk PDFs not referenced by any payslip row', $unreferencedOnDisk->count()],
        ]);

        if ($missingSamples !== []) {
            $this->warn('DB paths missing on disk (sample):');
            $this->table(['Matricule', 'Expected file', 'On disk'], $missingSamples);
            $failed = true;
        }

        if ($unreferencedOnDisk->isNotEmpty()) {
            $this->line('Unreferenced on disk (sample): ' . $unreferencedOnDisk->take(5)->map(fn ($p) => basename($p))->implode(', '));
        }

        if ($diskFiles->isNotEmpty() && $payslipsWithFile->isEmpty()) {
            $this->warn('PDFs exist on disk in this folder but no payslip rows on this process reference them.');
            $failed = true;
        }

        return $failed;
    }

    /**
     * @return list<string>
     */
    private function extractMatriculesFromText(string $text): array
    {
        if (preg_match_all('/Matricule\s+([A-Z0-9]+)/iu', $text, $matches)) {
            return array_values(array_unique($matches[1]));
        }

        return [];
    }
}
