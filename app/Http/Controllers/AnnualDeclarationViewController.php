<?php

namespace App\Http\Controllers;

use App\Models\AnnualDeclaration;
use Illuminate\Support\Facades\Storage;

class AnnualDeclarationViewController extends Controller
{
    public function viewPdf($id)
    {
        $declaration = AnnualDeclaration::findOrFail($id);
        $user = auth()->user() ?? abort(401);

        $allowed = match ($user->getRoleNames()->first()) {
            'employee' => $declaration->isVisibleToEmployee($user),
            'supervisor' => $user->supDepartments->pluck('department_id')->contains($declaration->department_id),
            'manager' => $user->managerCompanies->pluck('id')->contains($declaration->company_id),
            'admin' => true,
            default => false,
        };
        abort_unless($allowed, 403);

        if ($declaration->page_status !== AnnualDeclaration::PAGE_MATCHED
            || (int) $declaration->encryption_status !== AnnualDeclaration::STATUS_SUCCESSFUL
            || empty($declaration->file)
            || !Storage::disk('modified')->exists($declaration->file)) {
            abort(404, __('annual_declarations.note_file_not_ready'));
        }

        $path = Storage::disk('modified')->path($declaration->file);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }
}
