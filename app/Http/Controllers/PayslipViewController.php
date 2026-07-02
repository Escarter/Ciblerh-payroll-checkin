<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\User;
use App\Services\PayslipEncryptionService;
use Illuminate\Support\Facades\Storage;

class PayslipViewController extends Controller
{
    public function viewPdf($id)
    {
        $payslip = Payslip::findOrFail($id);
        $user = auth()->user();
        
        if (!$user) {
            abort(401);
        }
        
        $role = $user->getRoleNames()->first();
        
        switch ($role) {
            case 'employee':
                if (!$payslip->isVisibleToEmployee($user)) {
                    abort(403);
                }
                break;
                
            case 'supervisor':
                $validDepartmentIds = $user->supDepartments->pluck('department_id')->toArray();
                if (!in_array($payslip->department_id, $validDepartmentIds)) {
                    abort(403, __('common.unauthorized_department_access'));
                }
                break;
                
            case 'admin':
            case 'manager':
                break;
                
            default:
                abort(403);
        }

        if (empty($payslip->file) || !Storage::disk('modified')->exists($payslip->file)) {
            abort(404, __('payslips.payslip_file_not_found'));
        }

        $encryptionEmployee = $role === 'employee'
            ? $user
            : (User::find($payslip->employee_id) ?? $user);

        if ((int) $payslip->encryption_status !== Payslip::STATUS_SUCCESSFUL) {
            if (!app(PayslipEncryptionService::class)->ensureEncrypted($payslip->fresh(), $encryptionEmployee)) {
                abort(404, __('payslips.encryption_not_successful'));
            }
        }
        
        try {
            $filePath = Storage::disk('modified')->path($payslip->file);
            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"'
            ]);
        } catch (\Exception $e) {
            abort(404, __('payslips.unable_to_view_payslip'));
        }
    }
}
