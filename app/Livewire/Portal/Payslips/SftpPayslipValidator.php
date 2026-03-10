<?php

namespace App\Livewire\Portal\Payslips;

use App\Models\Department;
use App\Models\Company;
use App\Models\PayslipMatchingProposal;
use App\Jobs\ProcessValidatedPayslipsJob;
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
        ])->layout('components.layouts.dashboard');
    }

    /**
     * Re-run company matching on an existing proposal's local file
     */
    public function rematch(string $proposalId): void
    {
        $proposal = PayslipMatchingProposal::findOrFail($proposalId);

        if (!$proposal->local_file_path || !file_exists($proposal->local_file_path)) {
            $this->dispatch('alert', ['type' => 'error', 'message' => __('payslips.local_file_not_found')]);
            return;
        }

        $service  = new SftpPayslipService();
        $metadata = $service->extractPdfMetadata($proposal->local_file_path);

        // Build match sources identical to ProcessSftpPushFileJob:
        // all extracted header lines + cleaned filename stem
        $matchSources = $metadata['company_header_lines'] ?? [];
        if (!empty($metadata['company_raw']) && !in_array($metadata['company_raw'], $matchSources, true)) {
            $matchSources[] = $metadata['company_raw'];
        }

        $filename     = pathinfo($proposal->local_file_path, PATHINFO_FILENAME);
        $filenameStem = preg_replace('/[-_\.]+/', ' ', $filename);
        $filenameStem = preg_replace('/\b(19|20)\d{2}\b/', '', $filenameStem);
        $filenameStem = preg_replace('/\b\d{1,2}\b/', '', $filenameStem);
        $filenameStem = preg_replace(
            '/\b(janvier|fevrier|mars|avril|mai|juin|juillet|aout|septembre|octobre|novembre|decembre|january|february|march|april|june|july|august|september|october|november|december)\b/iu',
            '',
            $filenameStem
        );
        $filenameStem = preg_replace('/\b(bulletin|paie|fiche|salaire|payslip|salary|sheet|payroll)\b/iu', '', $filenameStem);
        $filenameStem = trim(preg_replace('/\s+/', ' ', $filenameStem));
        if (!empty($filenameStem)) {
            $matchSources[] = $filenameStem;
        }

        $candidates = !empty($matchSources)
            ? $service->matchBestFromMultiple($matchSources)
            : [];

        $best = $candidates[0] ?? null;

        $proposal->update([
            'proposed_match' => [
                'company_raw' => $metadata['company_raw'],
                'candidates'  => $candidates,
                'best_match'  => $best,
            ],
            'matched_to_company_id'    => $best['company_id']    ?? $proposal->matched_to_company_id,
            'matched_to_department_id' => $proposal->matched_to_department_id,
            'matched_month'            => $metadata['month']     ?? $proposal->matched_month,
            'matched_year'             => $metadata['year']      ?? $proposal->matched_year,
            'raw_text_preview'         => $metadata['raw_text_preview'] ?? $proposal->raw_text_preview,
        ]);

        $this->dispatch('alert', [
            'type'    => empty($candidates) ? 'warning' : 'success',
            'message' => empty($candidates)
                ? __('payslips.rematch_no_candidates')
                : __('payslips.rematch_found', ['count' => count($candidates)]),
        ]);
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

        // Dispatch the processing job
        ProcessValidatedPayslipsJob::dispatch($proposal)->onQueue('processing');

        $this->dispatch('alert', [
            'type' => 'success',
            'message' => __('payslips.proposal_validated_and_queued')
        ]);

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
            ['status' => PayslipMatchingProposal::STATUS_VALIDATED],
            ['status' => PayslipMatchingProposal::STATUS_REJECTED, 'rejection_reason' => $this->editingReason]
        );

        $this->dispatch('alert', [
            'type' => 'warning',
            'message' => __('payslips.proposal_rejected')
        ]);

        $this->resetEditForm();
        $this->dispatch('closeModals');
    }

    /**
     * Process multiple validated proposals
     */
    public function processSelected()
    {
        if (empty($this->selectedProposals)) {
            $this->dispatch('alert', [
                'type' => 'warning',
                'message' => __('common.no_items_selected')
            ]);
            return;
        }

        $proposals = PayslipMatchingProposal::whereIn('id', $this->selectedProposals)
            ->where('status', PayslipMatchingProposal::STATUS_VALIDATED)
            ->get();

        foreach ($proposals as $proposal) {
            ProcessValidatedPayslipsJob::dispatch($proposal)->onQueue('processing');
        }

        $this->dispatch('alert', [
            'type' => 'success',
            'message' => __('payslips.proposals_queued_for_processing', ['count' => $proposals->count()])
        ]);

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
