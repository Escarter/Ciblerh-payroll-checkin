<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\SendPayslipProcess;
use App\Models\User;
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
                            {--sample-pages=5 : Max splitted pages to scan when comparing pool (0 = all)}';

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

        $employees = $this->resolveEmployeePool($process);
        $this->line('Employee pool size: ' . $employees->count());

        $splittedDir = $process->destination_directory;
        $files = collect(Storage::disk('splitted')->allFiles($splittedDir))
            ->filter(fn (string $file) => str_ends_with(strtolower($file), '.pdf'))
            ->values();

        $modifiedCount = collect(Storage::disk('modified')->allFiles($splittedDir))
            ->filter(fn (string $file) => str_ends_with(strtolower($file), '.pdf'))
            ->count();

        $this->line("Splitted pages: {$files->count()} | Modified PDFs: {$modifiedCount}");

        if ($files->isEmpty()) {
            $this->warn('No splitted PDF pages found for this process directory.');
            $failed = true;
        } else {
            $sampleLimit = max(0, (int) $this->option('sample-pages'));
            $filesToScan = $sampleLimit === 0 ? $files : $files->take($sampleLimit);

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
        }

        return $failed;
    }

    private function resolveEmployeePool(SendPayslipProcess $process): Collection
    {
        if ($process->department_id) {
            $department = Department::withTrashed()->find($process->department_id);

            return $department ? $department->employees : collect();
        }

        return User::where('company_id', $process->company_id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'employee'))
            ->get();
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
