<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class PayslipViewController extends Controller
{
    public function viewPdf($id)
    {
        $payslip = Payslip::findOrFail($id);
        
        // Check if encryption was successful
        if ($payslip->encryption_status != 1) {
            abort(404, __('payslips.encryption_not_successful'));
        }

        // Check if the file exists
        if (!Storage::disk('modified')->exists($payslip->file)) {
            abort(404, __('payslips.payslip_file_not_found'));
        }

        // Authorization check
        $user = auth()->user();
        
        if (!$user) {
            abort(401);
        }
        
        $role = $user->getRoleNames()->first();
        
        // Authorization checks based on role
        switch ($role) {
            case 'employee':
                // Employee can only view their own payslips
                if ($payslip->employee_id !== $user->id) {
                    abort(403);
                }
                break;
                
            case 'supervisor':
                // Supervisor can only view payslips from their departments
                $validDepartmentIds = $user->supDepartments->pluck('department_id')->toArray();
                if (!in_array($payslip->department_id, $validDepartmentIds)) {
                    abort(403, __('common.unauthorized_department_access'));
                }
                break;
                
            case 'admin':
            case 'manager':
                // Admin and manager have full access - no additional checks needed
                break;
                
            default:
                // Unknown role - deny access
                abort(403);
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

