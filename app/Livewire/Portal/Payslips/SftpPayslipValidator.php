<?php

namespace App\Livewire\Portal\Payslips;

use App\Models\Department;
use App\Models\Company;
use App\Models\PayslipMatchingProposal;
use App\Jobs\ProcessValidatedPayslipsJob;
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
