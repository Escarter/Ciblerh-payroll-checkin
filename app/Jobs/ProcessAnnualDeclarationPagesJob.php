<?php

namespace App\Jobs;

use App\Models\AnnualDeclaration;
use App\Models\Department;
use App\Models\SendAnnualDeclarationProcess;
use App\Models\User;
use App\Services\AnnualDeclarationParser;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use mikehaertl\pdftk\Pdf;

/**
 * Parses split pages, matches each page to an employee by matricule, records it
 * (matched / unmatched / empty) and encrypts matched pages with the employee's PDF password.
 */
class ProcessAnnualDeclarationPagesJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $timeout = 600;

    public function __construct(protected Collection $files, protected int $processId)
    {
        $this->onQueue('pdf-processing');
    }

    public function handle(AnnualDeclarationParser $parser): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        $process = SendAnnualDeclarationProcess::findOrFail($this->processId);
        $employees = $this->employeePool($process);

        Storage::disk('modified')->makeDirectory($process->destination_directory);

        foreach ($this->files as $file) {
            $pageNumber = $this->pageNumber($file);

            try {
                $data = $parser->parseFile(Storage::disk('splitted')->path($file));
            } catch (\Throwable $e) {
                Log::warning('Annual declaration page could not be parsed', ['file' => $file, 'error' => $e->getMessage()]);
                $this->recordReviewPage($process, $pageNumber, AnnualDeclaration::PAGE_UNMATCHED, [], $e->getMessage());
                continue;
            }

            if (!$data['is_declaration'] || empty($data['matricule'])) {
                $this->recordReviewPage($process, $pageNumber, AnnualDeclaration::PAGE_UNMATCHED, $data, __('annual_declarations.note_no_matricule'));
                continue;
            }

            if ($data['year'] !== null && (int) $data['year'] !== (int) $process->year) {
                $this->recordReviewPage($process, $pageNumber, AnnualDeclaration::PAGE_UNMATCHED, $data, __('annual_declarations.note_year_mismatch', ['found' => $data['year'], 'expected' => $process->year]));
                continue;
            }

            $employee = $employees->get($data['matricule']);

            if (!$employee) {
                $this->recordReviewPage($process, $pageNumber, AnnualDeclaration::PAGE_UNMATCHED, $data, __('annual_declarations.note_employee_not_found'));
                continue;
            }

            if ($data['is_empty']) {
                $this->recordReviewPage($process, $pageNumber, AnnualDeclaration::PAGE_EMPTY, $data, __('annual_declarations.note_empty_page'), $employee);
                continue;
            }

            $this->recordMatchedPage($process, $file, $pageNumber, $data, $employee);
        }
    }

    private function employeePool(SendAnnualDeclarationProcess $process): Collection
    {
        $employees = $process->department_id
            ? Department::withTrashed()->findOrFail($process->department_id)->employees
            : User::where('company_id', $process->company_id)
                ->whereHas('roles', fn ($q) => $q->where('name', 'employee'))
                ->get();

        return $employees
            ->filter(fn ($e) => !empty($e->matricule))
            ->keyBy(fn ($e) => User::normalizeMatricule($e->matricule));
    }

    private function pageNumber(string $file): int
    {
        return preg_match('/page_(\d+)\.pdf$/', $file, $m) ? (int) $m[1] : 0;
    }

    private function recordReviewPage(SendAnnualDeclarationProcess $process, int $pageNumber, string $status, array $data, ?string $note, ?User $employee = null): void
    {
        AnnualDeclaration::updateOrCreate(
            ['send_annual_declaration_process_id' => $process->id, 'page_number' => $pageNumber],
            [
                'user_id' => $process->user_id,
                'employee_id' => $employee?->id,
                'company_id' => $employee?->company_id ?? $process->company_id,
                'department_id' => $employee?->department_id ?? $process->department_id,
                'page_status' => $status,
                'first_name' => $employee?->first_name,
                'last_name' => $employee?->last_name,
                'extracted_name' => $data['name'] ?? null,
                'matricule' => $data['matricule'] ?? null,
                'cnps_number' => $data['cnps_number'] ?? null,
                'year' => $process->year,
                'gross_amount' => $data['gross_amount'] ?? null,
                'tax_total' => $data['tax_total'] ?? null,
                'failure_reason' => $note,
                'email_sent_status' => AnnualDeclaration::STATUS_SKIPPED,
                'sms_sent_status' => AnnualDeclaration::STATUS_SKIPPED,
            ]
        );
    }

    private function recordMatchedPage(SendAnnualDeclarationProcess $process, string $file, int $pageNumber, array $data, User $employee): void
    {
        $existing = AnnualDeclaration::where('company_id', $employee->company_id)
            ->where('matricule', $data['matricule'])
            ->where('year', $process->year)
            ->where('page_status', AnnualDeclaration::PAGE_MATCHED)
            ->first();

        // Already delivered in a previous run: never resend automatically.
        if ($existing && (int) $existing->email_sent_status === AnnualDeclaration::STATUS_SUCCESSFUL) {
            return;
        }

        $relativePath = $process->destination_directory . '/' . $data['matricule'] . '_' . $process->year . '.pdf';

        $record = AnnualDeclaration::updateOrCreate(
            ['company_id' => $employee->company_id, 'matricule' => $data['matricule'], 'year' => $process->year],
            [
                'send_annual_declaration_process_id' => $process->id,
                'user_id' => $process->user_id,
                'employee_id' => $employee->id,
                'department_id' => $employee->department_id,
                'page_number' => $pageNumber,
                'page_status' => AnnualDeclaration::PAGE_MATCHED,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'extracted_name' => $data['name'],
                'email' => $employee->email,
                'phone' => $employee->professional_phone_number ?: $employee->personal_phone_number,
                'cnps_number' => $data['cnps_number'],
                'gross_amount' => $data['gross_amount'],
                'tax_total' => $data['tax_total'],
                'file' => $relativePath,
                'encryption_status' => AnnualDeclaration::STATUS_PENDING,
                'email_sent_status' => AnnualDeclaration::STATUS_PENDING,
                'sms_sent_status' => AnnualDeclaration::STATUS_PENDING,
                'failure_reason' => null,
            ]
        );

        $this->encrypt($record, $employee, Storage::disk('splitted')->path($file));
    }

    private function encrypt(AnnualDeclaration $record, User $employee, string $sourcePath): void
    {
        if (empty($employee->pdf_password)) {
            $record->update([
                'encryption_status' => AnnualDeclaration::STATUS_FAILED,
                'encryption_status_note' => __('annual_declarations.note_missing_pdf_password'),
            ]);
            return;
        }

        try {
            $pdf = new Pdf($sourcePath, ['command' => config('ciblerh.pdftk_path')]);
            $saved = $pdf->setUserPassword($employee->pdf_password)
                ->passwordEncryption(128)
                ->saveAs(Storage::disk('modified')->path($record->file));

            if (!$saved) {
                throw new \RuntimeException($pdf->getError() ?: 'pdftk failed');
            }

            $record->update(['encryption_status' => AnnualDeclaration::STATUS_SUCCESSFUL, 'encryption_status_note' => null]);
        } catch (\Throwable $e) {
            Log::error('Annual declaration encryption failed', ['record_id' => $record->id, 'error' => $e->getMessage()]);
            $record->update(['encryption_status' => AnnualDeclaration::STATUS_FAILED, 'encryption_status_note' => $e->getMessage()]);
        }
    }
}
