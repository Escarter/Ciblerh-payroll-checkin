<?php

namespace App\Services;

use App\Models\Payslip;
use App\Models\SendPayslipProcess;
use App\Models\User;
use Escarter\PopplerPhp\PdfToText;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use mikehaertl\pdftk\Pdf;

class PayslipFileRecoveryService
{
    /**
     * Recover missing modified files from split files for one process.
     *
     * @return array{scanned:int,missing:int,recovered:int,skipped:int,errors:array<int,string>}
     */
    public function recoverProcess(SendPayslipProcess $process, bool $dryRun = false): array
    {
        $result = [
            'scanned' => 0,
            'missing' => 0,
            'recovered' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $splitFiles = Storage::disk('splitted')->allFiles($process->destination_directory);

        $payslips = Payslip::query()
            ->where('send_payslip_process_id', $process->id)
            ->get();

        foreach ($payslips as $payslip) {
            $result['scanned']++;

            $targetRelative = $this->resolveTargetRelativePath($process, $payslip);
            if ($targetRelative !== '' && Storage::disk('modified')->exists($targetRelative)) {
                continue;
            }

            $result['missing']++;

            if (empty($payslip->matricule)) {
                $result['skipped']++;
                $result['errors'][] = "Payslip #{$payslip->id}: missing matricule.";
                continue;
            }

            if (empty($splitFiles)) {
                $result['skipped']++;
                $result['errors'][] = "Payslip #{$payslip->id}: no split files found for directory {$process->destination_directory}.";
                continue;
            }

            $sourceSplit = $this->findSplitFileForMatricule($splitFiles, $payslip->matricule);
            if ($sourceSplit === null) {
                $result['skipped']++;
                $result['errors'][] = "Payslip #{$payslip->id} matricule {$payslip->matricule}: no matching split file found.";
                continue;
            }

            $employee = User::find($payslip->employee_id);
            if (!$employee || empty($employee->pdf_password)) {
                $result['skipped']++;
                $result['errors'][] = "Payslip #{$payslip->id}: employee/password missing.";
                continue;
            }

            if ($dryRun) {
                $result['recovered']++;
                continue;
            }

            try {
                $this->encryptSplitIntoModified($sourceSplit, $targetRelative, $employee->pdf_password);

                $payslip->update([
                    'file' => $targetRelative,
                    'encryption_status' => Payslip::STATUS_SUCCESSFUL,
                    'failure_reason' => null,
                    'encryption_status_note' => null,
                ]);

                $result['recovered']++;
            } catch (\Throwable $e) {
                $result['skipped']++;
                $result['errors'][] = "Payslip #{$payslip->id}: recovery failed - {$e->getMessage()}";
                Log::error('Payslip file recovery failed', [
                    'process_id' => $process->id,
                    'payslip_id' => $payslip->id,
                    'source_split' => $sourceSplit,
                    'target_file' => $targetRelative,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    private function resolveTargetRelativePath(SendPayslipProcess $process, Payslip $payslip): string
    {
        if (!empty($payslip->file)) {
            return (string) $payslip->file;
        }

        return $process->destination_directory . '/' . $payslip->matricule . '_' . $payslip->month . '.pdf';
    }

    private function findSplitFileForMatricule(array $splitFiles, string $matricule): ?string
    {
        foreach ($splitFiles as $splitFile) {
            if (!Storage::disk('splitted')->exists($splitFile)) {
                continue;
            }

            try {
                $text = PdfToText::getText(
                    Storage::disk('splitted')->path($splitFile),
                    config('ciblerh.pdftotext_path')
                );

                if (User::matriculeTokenExistsInPdfText((string) $text, $matricule)) {
                    return $splitFile;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function encryptSplitIntoModified(string $splitRelativePath, string $targetRelativePath, string $password): void
    {
        Storage::disk('modified')->makeDirectory(dirname($targetRelativePath));

        $sourcePath = Storage::disk('splitted')->path($splitRelativePath);
        $targetPath = Storage::disk('modified')->path($targetRelativePath);

        $pdf = new Pdf($sourcePath, ['command' => config('ciblerh.pdftk_path')]);
        $pdf->tempDir = config('ciblerh.temp_dir');

        $saved = $pdf->setUserPassword($password)
            ->passwordEncryption(128)
            ->saveAs($targetPath);

        if (!$saved || !Storage::disk('modified')->exists($targetRelativePath)) {
            throw new \RuntimeException('Encrypted output file was not created.');
        }
    }
}
