<?php

namespace App\Jobs\Plan;

use App\Jobs\ProcessAnnualDeclarationPagesJob;
use App\Jobs\SendAnnualDeclarationJob;
use App\Jobs\SplitAnnualDeclarationPdfJob;
use App\Models\AnnualDeclaration;
use App\Models\PayslipMatchingProposal;
use App\Models\SendAnnualDeclarationProcess;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AnnualDeclarationSendingPlan
{
    public static function start(SendAnnualDeclarationProcess $process): void
    {
        $process->update(['status' => SendAnnualDeclarationProcess::STATUS_PROCESSING, 'failure_reason' => null]);

        Bus::chain([
            new SplitAnnualDeclarationPdfJob($process),
            function () use ($process) {
                static::step2($process);
            },
        ])->catch(function (\Throwable $e) use ($process) {
            static::failed($process, $e->getMessage());
        })->dispatch();
    }

    private static function step2(SendAnnualDeclarationProcess $process): void
    {
        try {
            $files = Storage::disk('splitted')->allFiles($process->destination_directory);
        } catch (\Throwable $e) {
            static::failed($process, $e->getMessage());
            return;
        }

        if (count($files) === 0) {
            static::failed($process, __('payslips.no_splitted_files_to_process'));
            return;
        }

        $process->update(['total_pages' => count($files)]);

        $jobs = collect($files)->chunk((int) config('ciblerh.chunk_size', 20))
            ->map(fn ($chunk) => new ProcessAnnualDeclarationPagesJob($chunk->values(), $process->id));

        Bus::batch($jobs)
            ->onQueue('pdf-processing')
            ->then(fn ($batch) => static::step3($process))
            ->catch(fn ($batch, $e) => static::failed($process, $e instanceof \Throwable ? $e->getMessage() : null))
            ->allowFailures()
            ->name('Parse, match and encrypt annual declarations')
            ->dispatch();
    }

    private static function step3(SendAnnualDeclarationProcess $process): void
    {
        $ids = AnnualDeclaration::where('send_annual_declaration_process_id', $process->id)
            ->sendable()
            ->where('email_sent_status', AnnualDeclaration::STATUS_PENDING)
            ->pluck('id');

        if ($ids->isEmpty()) {
            static::complete($process);
            return;
        }

        $jobs = $ids->chunk((int) config('ciblerh.email_chunk_size', 15))
            ->map(fn ($chunk) => new SendAnnualDeclarationJob($chunk->values()->all()));

        Bus::batch($jobs)
            ->onQueue('emails')
            ->then(function ($batch) use ($process) {
                $process->update(['batch_id' => $batch->id]);
                static::complete($process);
            })
            ->catch(fn ($batch, $e) => static::failed($process, $e instanceof \Throwable ? $e->getMessage() : null))
            ->allowFailures()
            ->name('Send annual declarations')
            ->dispatch();
    }

    private static function complete(SendAnnualDeclarationProcess $process): void
    {
        $process->update(['status' => SendAnnualDeclarationProcess::STATUS_SUCCESSFUL, 'percentage_completion' => 100]);

        static::updateProposal($process, PayslipMatchingProposal::STATUS_PROCESSED, ['processed_at' => now()]);
    }

    private static function failed(SendAnnualDeclarationProcess $process, ?string $reason = null): void
    {
        Log::error('AnnualDeclarationSendingPlan failed', ['process_id' => $process->id, 'reason' => $reason]);

        $process->update([
            'status' => SendAnnualDeclarationProcess::STATUS_FAILED,
            'failure_reason' => $reason ?: __('annual_declarations.process_failed'),
        ]);

        static::updateProposal($process, PayslipMatchingProposal::STATUS_FAILED, ['rejection_reason' => $reason]);
    }

    private static function updateProposal(SendAnnualDeclarationProcess $process, string $status, array $extra): void
    {
        if (empty($process->sftp_proposal_id)) {
            return;
        }

        PayslipMatchingProposal::where('id', $process->sftp_proposal_id)
            ->whereIn('status', [PayslipMatchingProposal::STATUS_VALIDATED, PayslipMatchingProposal::STATUS_PENDING])
            ->update(array_merge(['status' => $status], $extra));
    }
}
