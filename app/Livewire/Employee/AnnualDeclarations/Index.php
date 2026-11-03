<?php

namespace App\Livewire\Employee\AnnualDeclarations;

use App\Livewire\Traits\WithDataTable;
use App\Models\AnnualDeclaration;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Index extends Component
{
    use WithDataTable;

    public function download(int $id)
    {
        $declaration = AnnualDeclaration::findOrFail($id);
        abort_unless($declaration->isVisibleToEmployee(auth()->user()), 403, __('common.unauthorized_access'));

        if ((int) $declaration->encryption_status !== AnnualDeclaration::STATUS_SUCCESSFUL
            || empty($declaration->file)
            || !Storage::disk('modified')->exists($declaration->file)) {
            $this->showToast(__('annual_declarations.note_file_not_ready'), 'danger');
            return;
        }

        return response()->download(
            Storage::disk('modified')->path($declaration->file),
            $declaration->matricule . '_declaration_' . $declaration->year . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function render()
    {
        $declarations = AnnualDeclaration::visibleToEmployee(auth()->user())
            ->orderByDesc('year')
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.employee.annual-declarations.index', compact('declarations'))
            ->layout('components.layouts.employee.master');
    }
}
