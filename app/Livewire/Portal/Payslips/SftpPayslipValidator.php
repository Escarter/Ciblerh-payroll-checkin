<?php

namespace App\Livewire\Portal\Payslips;

use App\Models\Department;
use App\Models\Company;
use App\Models\PayslipMatchingProposal;
use App\Models\SendPayslipProcess;
use App\Jobs\ProcessValidatedPayslipsJob;
use App\Services\FeatureConfigurationService;
use App\Services\SftpPayslipService;
use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Traits\WithDataTable;

class SftpPayslipValidator extends Component
{
    use WithPagination, WithDataTable;

    public $selectedProposals = [];
    public $filterStatus = 'pending';
    public $filterCompany = '';
    public $filterDepartment = '';
    public $searchFilename = '';

    // Modal properties
    public $editingProposalId = null;
    public $editingDepartmentId = '';
    public $editingCompanyId = '';
    public $editingMonth = '';
    public $editingYear = '';
    public $editingReason = '';

    // View modal
    public $viewingProposalId = null;

    public function mount()
    {
        $this->authorize('manage-payslips');

        // Handle deep-link from notification email: ?proposal=UUID&mode=view|edit
        $proposalId = request('proposal');
        $mode       = request('mode', 'view');

        if ($proposalId) {
            $proposal = PayslipMatchingProposal::find($proposalId);

            if ($proposal) {
                // Switch the tab to match the proposal's current status
                $this->filterStatus = $proposal->status;

                if ($mode === 'edit' && $proposal->status === PayslipMatchingProposal::STATUS_PENDING) {
                    $this->editingProposalId   = $proposal->id;
                    $this->editingCompanyId    = $proposal->matched_to_company_id;
                    $this->editingDepartmentId = $proposal->matched_to_department_id;
                    $this->editingMonth        = $proposal->matched_month;
                    $this->editingYear         = $proposal->matched_year;
                    $this->dispatch('openEditModal');
                } else {
                    $this->viewingProposalId = $proposal->id;
                    $this->dispatch('openViewModal');
                }
            }
        }
    }

    public function render()
    {
        $query = PayslipMatchingProposal::query();

        // Filter by status
        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        // Filter by company
        if (!empty($this->filterCompany)) {
            $query->where('matched_to_company_id', $this->filterCompany);
        }

        // Filter by department
        if (!empty($this->filterDepartment)) {
            $query->where('matched_to_department_id', $this->filterDepartment);
        }

        // Search by filename
        if (!empty($this->searchFilename)) {
            $query->where('file_name', 'like', '%' . $this->searchFilename . '%');
        }

        $proposals = $query->orderBy('created_at', 'desc')->paginate(15);

        // Load all companies
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        
        // Load departments filtered by selected company
        if (!empty($this->filterCompany)) {
            $departmentsForFilter = Department::where('company_id', $this->filterCompany)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $departmentsForFilter = collect(); // Empty collection if no company selected
        }

        // Load departments for modal (filtered by editing company)
        if (!empty($this->editingCompanyId)) {
            $departments = Department::where('company_id', $this->editingCompanyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $departments = collect(); // Empty collection if no company selected
        }

        return view('livewire.portal.payslips.sftp-validator', [
            'proposals' => $proposals,
            'departments' => $departments,
            'departmentsForFilter' => $departmentsForFilter,
            'companies' => $companies,
            'viewingProposal' => $this->viewingProposalId
                ? PayslipMatchingProposal::find($this->viewingProposalId)
                : null,
            'totalPending' => PayslipMatchingProposal::pending()->count(),
            'totalValidated' => PayslipMatchingProposal::validated()->count(),
            'totalProcessed' => PayslipMatchingProposal::processed()->count(),
            'totalRejected' => PayslipMatchingProposal::rejected()->count(),
            'totalFailed' => PayslipMatchingProposal::failed()->count(),
        ])->layout('components.layouts.dashboard');
    }

    /**
     * Return null when proposal is ready for processing, otherwise a translated
     * warning message describing the blocking condition.
     */
    private function getProcessingBlockReason(PayslipMatchingProposal $proposal): ?string
    {
        if (!$proposal->matched_to_company_id) {
            return __('payslips.processing_blocked_no_company');
        }

        if ($proposal->download_status !== 'downloaded') {
            return __('payslips.processing_blocked_not_downloaded');
        }

        if (empty($proposal->local_file_path)) {
            return __('payslips.processing_blocked_missing_local_path');
        }

        $resolvedPath = $proposal->resolveExistingLocalFilePath();
        if (!$resolvedPath || !file_exists($resolvedPath)) {
            return __('payslips.processing_blocked_local_file_missing');
        }

        return null;
    }

    /**
     * Trigger an immediate scan of SFTP push incoming folders.
     * This lets admins pull newly uploaded files without waiting for scheduler.
     */
    public function pullNow(): void
    {
        $this->authorize('manage-payslips');

        try {
            \Artisan::call('sftp:scan-push-folder');
            $output = trim((string) \Artisan::output());
            $lower  = mb_strtolower($output);

            if (str_contains($lower, 'sftp sync is disabled')) {
                $this->dispatch('showToast',
                    message: __('payslips.pull_now_sync_disabled'),
                    type: 'warning'
                );
                return;
            }

            if (preg_match('/Dispatched:\s*(\d+),\s*Skipped:\s*(\d+)/i', $output, $m)) {
                $this->dispatch('showToast',
                    message: __('payslips.pull_now_success', [
                        'queued' => (int) $m[1],
                        'skipped' => (int) $m[2],
                    ]),
                    type: 'success'
                );
            } else {
                $this->dispatch('showToast',
                    message: __('payslips.pull_now_done'),
                    type: 'success'
                );
            }

            $this->resetPage();
        } catch (\Throwable $e) {
            $this->dispatch('showToast',
                message: __('payslips.pull_now_failed', ['error' => $e->getMessage()]),
                type: 'danger'
            );
        }
    }

    /**
     * Re-run company matching on an existing proposal's local file
     */
    public function rematch(string $proposalId): void
    {
        $proposal = PayslipMatchingProposal::findOrFail($proposalId);
        $resolvedPath = $proposal->resolveExistingLocalFilePath();

        if (!$resolvedPath || !file_exists($resolvedPath)) {
            $this->dispatch('showToast', message: __('payslips.local_file_not_found'), type: 'danger');
            return;
        }

        $service  = new SftpPayslipService();
        $metadata = $service->extractPdfMetadata($resolvedPath);

        $matchSources = $service->buildCompanyMatchSources(
            $metadata,
            $proposal->file_name ?: basename($resolvedPath)
        );

        $candidates = !empty($matchSources)
            ? $service->matchBestFromMultiple($matchSources)
            : [];

        $best = $candidates[0] ?? null;

        $proposal->update([
            'proposed_match' => [
                'company_raw'          => $metadata['company_raw'],
                'company_header_lines' => $metadata['company_header_lines'] ?? [],
                'match_sources'        => $matchSources,
                'raw_text_preview'     => $metadata['raw_text_preview'] ?? null,
                'candidates'           => $candidates,
                'best_match'           => $best,
            ],
            'raw_text_preview' => $metadata['raw_text_preview'] ?? $proposal->raw_text_preview,
            'matched_to_company_id'    => $best['company_id']    ?? $proposal->matched_to_company_id,
            'matched_to_department_id' => $proposal->matched_to_department_id,
            'matched_month'            => $metadata['month']     ?? $proposal->matched_month,
            'matched_year'             => $metadata['year']      ?? $proposal->matched_year,
        ]);

        // Auto-validate if confidence meets the configured threshold
        $autoValidated = false;
        if ($best !== null) {
            $autoMatchConfig = FeatureConfigurationService::getSftpAutoMatchConfig();
            if ($autoMatchConfig['enabled'] && FeatureConfigurationService::canAutoMatch($best, $autoMatchConfig)) {
                // Guard: check if already processing to prevent concurrent runs
                $existingProcess = SendPayslipProcess::where('sftp_proposal_id', $proposal->id)
                    ->where('status', '!=', 'successful')
                    ->where('status', '!=', 'failed')
                    ->first();
                if ($existingProcess) {
                    $this->dispatch('showToast',
                        message: __('payslips.proposal_already_processing'),
                        type: 'warning'
                    );
                    return;
                }

                try {
                    $proposal->update([
                        'status'          => PayslipMatchingProposal::STATUS_VALIDATED,
                        'is_auto_matched' => true,
                        'matched_at'      => now(),
                    ]);
                    // Use dispatchSync to prevent orphaned processing state if Livewire disconnect occurs
                    // File was already validated in rematch(), so we're safe to proceed synchronously
                    ProcessValidatedPayslipsJob::dispatchSync($proposal);
                    $autoValidated = true;
                } catch (\Throwable $e) {
                    \Log::error('SftpPayslipValidator::rematch() - dispatchSync failed', [
                        'proposal_id' => $proposal->id,
                        'error'       => $e->getMessage(),
                        'file'        => $e->getFile(),
                        'line'        => $e->getLine(),
                    ]);
                    $proposal->refresh(); // Reload to show current state
                    $this->dispatch('showToast',
                        message: __('payslips.rematch_processing_error', ['error' => $e->getMessage()]),
                        type: 'danger'
                    );
                    return;
                }
            }
        }

        $this->dispatch('showToast',
            message: empty($candidates)
                ? __('payslips.rematch_no_candidates')
                : ($autoValidated
                    ? __('payslips.rematch_auto_validated', ['count' => count($candidates)])
                    : __('payslips.rematch_found', ['count' => count($candidates)])),
            type: empty($candidates) ? 'warning' : 'success'
        );
    }

    /**
     * Open edit modal for a proposal
     */
    public function editProposal(string $proposalId)
    {
        $proposal = PayslipMatchingProposal::findOrFail($proposalId);

        $this->editingProposalId = $proposalId;
        $this->editingDepartmentId = $proposal->matched_to_department_id;
        $this->editingCompanyId = $proposal->matched_to_company_id;
        $this->editingMonth = $proposal->matched_month;
        $this->editingYear = $proposal->matched_year;

        $this->dispatch('openEditModal');
    }

    /**
     * Validate and save the match
     */
    public function validateMatch()
    {
        $this->validate([
            'editingCompanyId'   => 'required|integer|exists:companies,id',
            'editingDepartmentId'=> 'nullable|integer|exists:departments,id',
            'editingMonth'       => 'required|integer|min:1|max:12',
            'editingYear'        => 'required|integer|min:2020|max:2099',
        ]);

        $proposal = PayslipMatchingProposal::findOrFail($this->editingProposalId);

        $proposal->update([
            'matched_to_department_id' => $this->editingDepartmentId ?: null,
            'matched_to_company_id' => $this->editingCompanyId,
            'matched_month' => $this->editingMonth,
            'matched_year' => $this->editingYear,
            'matched_by_user_id' => auth()->id(),
            'matched_at' => now(),
            'status' => PayslipMatchingProposal::STATUS_VALIDATED,
        ]);

        if ($reason = $this->getProcessingBlockReason($proposal)) {
            $this->dispatch('showToast',
                message: __('payslips.proposal_validated_file_missing', ['reason' => $reason]),
                type: 'warning'
            );
            $this->resetEditForm();
            $this->dispatch('closeModals');
            return;
        }

        // Dispatch the processing job
        ProcessValidatedPayslipsJob::dispatch($proposal)->onQueue('processing');

        $this->dispatch('showToast', message: __('payslips.proposal_validated_and_queued'), type: 'success');

        $this->resetEditForm();
        $this->dispatch('closeModals');
    }

    /**
     * Open view modal to display proposal details
     */
    public function openViewModal(string $proposalId)
    {
        PayslipMatchingProposal::findOrFail($proposalId); // ensure exists
        $this->viewingProposalId = $proposalId;
        $this->dispatch('openViewModal');
    }

    /**
     * Reject a proposal
     */
    public function rejectProposal(string $proposalId)
    {
        $proposal = PayslipMatchingProposal::findOrFail($proposalId);

        $this->editingProposalId = $proposalId;
        $this->dispatch('openRejectModal');
    }

    /**
     * Save rejection
     */
    public function saveRejection()
    {
        $this->validate([
            'editingReason' => 'required|min:5|max:500',
        ]);

        $proposal = PayslipMatchingProposal::findOrFail($this->editingProposalId);
        $oldStatus = $proposal->status; // capture before update

        $proposal->update([
            'status' => PayslipMatchingProposal::STATUS_REJECTED,
            'rejection_reason' => $this->editingReason,
            'matched_by_user_id' => auth()->id(),
            'matched_at' => now(),
        ]);

        auditLog(
            auth()->user(),
            'sftp_proposal_rejected',
            'web',
            "Proposal {$proposal->id} for file {$proposal->file_name} rejected: {$this->editingReason}",
            $proposal,
            ['status' => $oldStatus],
            ['status' => PayslipMatchingProposal::STATUS_REJECTED, 'rejection_reason' => $this->editingReason]
        );

        $this->dispatch('showToast', message: __('payslips.proposal_rejected'), type: 'warning');

        $this->resetEditForm();
        $this->dispatch('closeModals');
    }

    /**
     * Process a single validated proposal
     */
    public function processSingle(string $proposalId): void
    {
        $proposal = PayslipMatchingProposal::findOrFail($proposalId);

        if ($proposal->status !== PayslipMatchingProposal::STATUS_VALIDATED) {
            $this->dispatch('showToast', message: __('payslips.proposal_not_validated'), type: 'warning');
            return;
        }

        if ($reason = $this->getProcessingBlockReason($proposal)) {
            $this->dispatch('showToast',
                message: __('payslips.proposal_not_ready_for_processing', ['reason' => $reason]),
                type: 'warning'
            );
            return;
        }

        // Guard: don't create a duplicate SendPayslipProcess if one is already in flight
        if ($proposal->sendPayslipProcesses()->where('status', 'processing')->exists()) {
            $this->dispatch('showToast', message: __('payslips.proposal_already_processing'), type: 'warning');
            return;
        }

        ProcessValidatedPayslipsJob::dispatch($proposal)->onQueue('processing');

        $this->dispatch('showToast',
            message: __('payslips.proposals_queued_for_processing', ['count' => 1]),
            type: 'success'
        );
    }

    /**
     * Process multiple validated proposals
     */
    public function processSelected()
    {
        if (empty($this->selectedProposals)) {
            $this->dispatch('showToast', message: __('common.no_items_selected'), type: 'warning');
            return;
        }

        $proposals = PayslipMatchingProposal::whereIn('id', $this->selectedProposals)
            ->where('status', PayslipMatchingProposal::STATUS_VALIDATED)
            ->get();

        $queued = 0;
        $skipped = 0;

        foreach ($proposals as $proposal) {
            if ($this->getProcessingBlockReason($proposal)) {
                $skipped++;
                continue;
            }

            // Skip if already in-flight
            if ($proposal->sendPayslipProcesses()->where('status', 'processing')->exists()) {
                $skipped++;
                continue;
            }

            ProcessValidatedPayslipsJob::dispatch($proposal)->onQueue('processing');
            $queued++;
        }

        $this->dispatch('showToast',
            message: __('payslips.proposals_queued_with_skips', [
                'queued' => $queued,
                'skipped' => $skipped,
            ]),
            type: $queued > 0 ? 'success' : 'warning'
        );

        $this->selectedProposals = [];
    }

    /**
     * Reset edit form
     */
    private function resetEditForm()
    {
        $this->editingProposalId = null;
        $this->viewingProposalId = null;
        $this->editingDepartmentId = '';
        $this->editingCompanyId = '';
        $this->editingMonth = '';
        $this->editingYear = '';
        $this->editingReason = '';
    }
}
