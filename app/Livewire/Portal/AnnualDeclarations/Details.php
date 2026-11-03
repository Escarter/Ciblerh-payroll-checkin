<?php

namespace App\Livewire\Portal\AnnualDeclarations;

use App\Jobs\Plan\AnnualDeclarationSendingPlan;
use App\Livewire\Traits\WithDataTable;
use App\Models\AnnualDeclaration;
use App\Models\SendAnnualDeclarationProcess;
use App\Services\AnnualDeclarationNotifier;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Details extends Component
{
    use \App\Livewire\Traits\AuthorizesAbilities;

    use WithDataTable;

    public SendAnnualDeclarationProcess $process;
    public string $tab = 'sendable';

    public function mount($id)
    {
        $this->authorizeAbility('payslip-read');

        $process = SendAnnualDeclarationProcess::withTrashed()->findOrFail($id);
        $this->authorizeProcess($process);
        $this->process = $process;
    }

    private function authorizeProcess(SendAnnualDeclarationProcess $process): void
    {
        $user = auth()->user();
        $allowed = match ($user->getRoleNames()->first()) {
            'admin' => true,
            'manager' => $user->managerCompanies->pluck('id')->contains($process->company_id),
            'supervisor' => $user->supDepartments->pluck('department_id')->contains($process->department_id),
            default => false,
        };

        abort_unless($allowed, 403);
    }

    public function switchTab(string $tab)
    {
        $this->tab = in_array($tab, ['sendable', 'review', 'failed'], true) ? $tab : 'sendable';
        $this->resetPage();
    }

    public function resend(int $id, AnnualDeclarationNotifier $notifier)
    {
        $this->authorizeAbility('payslip-sending');

        $declaration = $this->process->declarations()->sendable()->findOrFail($id);
        $notifier->send($declaration, true);

        $this->showToast(__('annual_declarations.resend_attempted'), 'success');
    }

    public function retryFailed()
    {
        $this->authorizeAbility('payslip-sending');

        $this->process->declarations()->sendable()
            ->where('email_sent_status', AnnualDeclaration::STATUS_FAILED)
            ->update(['email_sent_status' => AnnualDeclaration::STATUS_PENDING]);

        $failedEncryption = $this->process->declarations()->sendable()
            ->where('encryption_status', AnnualDeclaration::STATUS_FAILED)->exists();

        if ($failedEncryption) {
            // Re-run the whole plan: it re-encrypts pages and skips records already delivered.
            AnnualDeclarationSendingPlan::start($this->process);
        } else {
            \App\Jobs\SendAnnualDeclarationJob::dispatch(
                $this->process->declarations()->sendable()->where('email_sent_status', AnnualDeclaration::STATUS_PENDING)->pluck('id')->all()
            );
        }

        $this->showToast(__('annual_declarations.retry_started'), 'success');
    }

    public function render()
    {
        $query = $this->process->declarations();

        $query = match ($this->tab) {
            'review' => $query->needsReview(),
            'failed' => $query->sendable()->where(fn ($q) => $q->where('email_sent_status', AnnualDeclaration::STATUS_FAILED)
                ->orWhere('encryption_status', AnnualDeclaration::STATUS_FAILED)
                ->orWhere('sms_sent_status', AnnualDeclaration::STATUS_FAILED)),
            default => $query->sendable(),
        };

        if (!empty($this->query)) {
            $term = '%' . $this->query . '%';
            $query->where(fn ($q) => $q->where('matricule', 'like', $term)
                ->orWhere('first_name', 'like', $term)
                ->orWhere('last_name', 'like', $term)
                ->orWhere('extracted_name', 'like', $term));
        }

        $counts = [
            'sendable' => $this->process->declarations()->sendable()->count(),
            'review' => $this->process->declarations()->needsReview()->count(),
            'failed' => $this->process->declarations()->sendable()->where(fn ($q) => $q->where('email_sent_status', AnnualDeclaration::STATUS_FAILED)
                ->orWhere('encryption_status', AnnualDeclaration::STATUS_FAILED)
                ->orWhere('sms_sent_status', AnnualDeclaration::STATUS_FAILED))->count(),
        ];

        $this->process->refresh();

        return view('livewire.portal.annual-declarations.details', [
            'declarations' => $query->orderBy('page_number')->paginate($this->perPage),
            'counts' => $counts,
        ])->layout('components.layouts.dashboard');
    }
}
