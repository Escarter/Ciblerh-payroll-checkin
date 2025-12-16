<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Logs Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for audit log action types.
    | These translations will be displayed in the audit logs interface.
    |
    */

    // User actions
    'user_created' => 'User Created',
    'user_updated' => 'User Updated',
    'user_deleted' => 'User Deleted',
    'user_login' => 'User Login',
    'user_logout' => 'User Logout',

    // Company actions
    'company_created' => 'Company Created',
    'company_updated' => 'Company Updated',
    'company_deleted' => 'Company Deleted',
    'company_imported' => 'Company Imported',
    'company_exported' => 'Company Exported',

    // Department actions
    'department_created' => 'Department Created',
    'department_updated' => 'Department Updated',
    'department_deleted' => 'Department Deleted',

    // Service actions
    'service_created' => 'Service Created',
    'service_updated' => 'Service Updated',
    'service_deleted' => 'Service Deleted',
    'service_force_deleted' => 'Service Force Deleted',

    // Advance Salary actions
    'advanceSalary_created' => 'Advance Salary Created',
    'advanceSalary_updated' => 'Advance Salary Updated',
    'advanceSalary_deleted' => 'Advance Salary Deleted',
    'advanceSalary_approved' => 'Advance Salary Approved',
    'advanceSalary_rejected' => 'Advance Salary Rejected',

    // Absence actions
    'absence_created' => 'Absence Created',
    'absence_updated' => 'Absence Updated',
    'absence_deleted' => 'Absence Deleted',
    'absence_approved' => 'Absence Approved',
    'absence_rejected' => 'Absence Rejected',

    // Overtime actions
    'overtime_created' => 'Overtime Created',
    'overtime_updated' => 'Overtime Updated',
    'overtime_deleted' => 'Overtime Deleted',
    'overtime_approved' => 'Overtime Approved',
    'overtime_rejected' => 'Overtime Rejected',
    'overtime_exported' => 'Overtime Exported',

    // Check-in actions
    'checkin_created' => 'Check-in Created',
    'checkin_updated' => 'Check-in Updated',
    'checkin_deleted' => 'Check-in Deleted',
    'checkin_approved' => 'Check-in Approved',
    'checkin_rejected' => 'Check-in Rejected',

    // Payslip actions
    'payslip_sending' => 'Payslip Sending',
    'payslip_sent' => 'Payslip Sent',
    'payslip_failed' => 'Payslip Failed',
    'delete_payslip_process' => 'Delete Payslip Process',
    'force_delete_payslip_process' => 'Force Delete Payslip Process',
    'bulk_delete_payslip_process' => 'Bulk Delete Payslip Process',
    'bulk_force_delete_payslip_process' => 'Bulk Force Delete Payslip Process',

    // Leave type actions
    'leave_type_created' => 'Leave Type Created',
    'leave_type_updated' => 'Leave Type Updated',
    'leave_type_deleted' => 'Leave Type Deleted',
    'leave_type_imported' => 'Leave Type Imported',
    'leave_type_exported' => 'Leave Type Exported',

    // Report actions
    'report_generated' => 'Report Generated',
    'report_exported' => 'Report Exported',
    'payslip_report' => 'Payslip Report Generated',

    // Email/SMS actions
    'email_sent' => 'Email Sent',
    'sms_sent' => 'SMS Sent',

    // Role actions
    'role_created' => 'Role Created',
    'role_updated' => 'Role Updated',
    'role_deleted' => 'Role Deleted',

    // Action Perform Messages

    // Login/Logout
    'login_successful' => 'Successfully logged in from IP :ip',
    'logout_successful' => 'Successfully logged out from IP :ip',
    'login_contract_expired' => 'Tried to log in from IP :ip but contract has expired!',
    'login_account_banned' => 'Tried to log in from IP :ip but account is banned!',

    // Observer messages
    'created_absence' => 'Created an absence with date :date',
    'updated_absence' => 'Updated the absence from :user with date :date',
    'deleted_absence' => 'Deleted absence from :user with date :date',
    'approved_absence' => 'Approved the absence from :user with date :date',
    'rejected_absence' => 'Rejected the absence from :user with date :date',

    'created_overtime' => 'Created overtime record for the date :date',
    'updated_overtime' => 'Updated the overtime from :user with date :date',
    'deleted_overtime' => 'Deleted overtime record for :user for the date :date',
    'approved_overtime' => 'Approved the overtime from :user with date :date',
    'rejected_overtime' => 'Rejected the overtime from :user with date :date',

    'created_checkin' => 'Created checkin record for :user for the date :date',
    'updated_checkin' => 'Updated the checkin for :user for the date :date',
    'deleted_checkin' => 'Deleted checkin record for :user for the date :date',
    'approved_checkin_supervisor' => 'Supervisor approved the checkin for :user for the date :date',
    'rejected_checkin_supervisor' => 'Supervisor rejected the checkin for :user for the date :date',
    'approved_checkin_manager' => 'Manager approved the checkin for :user for the date :date',
    'rejected_checkin_manager' => 'Manager rejected the checkin for :user for the date :date',

    'created_advance_salary' => 'Created advance salary of amount :amount',
    'updated_advance_salary' => 'Updated the advance salary by :user of amount :amount',
    'deleted_advance_salary' => 'Deleted advance salary by :user of amount :amount',
    'approved_advance_salary' => 'Approved the advance salary by :user of amount :amount',
    'rejected_advance_salary' => 'Rejected the advance salary by :user of amount :amount',

    'updated_service' => 'Updated service with name :name',
    'deleted_service' => 'Deleted service with name :name',
    'permanently_deleted_service' => 'Permanently deleted service with name :name',

    // CRUD Operations
    'created_entity' => 'Created :entity with name :name',
    'updated_entity' => 'Updated :entity with name :name',
    'deleted_entity' => 'Deleted :entity with name :name',
    'force_deleted_entity' => 'Force deleted :entity with name :name',

    // Import/Export Operations
    'imported_entities' => 'Imported Excel file for :entities',
    'imported_entities_for_company' => 'Imported Excel file for :entities for company :company',
    'imported_entities_for_department' => 'Imported Excel file for :entities for department :department',
    'exported_entities' => 'Exported Excel file for :entities',
    'exported_entities_for_company' => 'Exported Excel file for :entities for company :company',
    'exported_entities_for_department' => 'Exported Excel file for :entities for department :department',

    // Payslip Operations
    'payslip_process_deleted' => 'Deleted payslip process :month-:year @ :time',
    'payslip_process_bulk_deleted' => 'Bulk deleted payslip processes',
    'payslip_process_bulk_force_deleted' => 'Bulk force deleted payslip processes',
    'payslip_report_generated' => 'Generated payslip report',

    // Approval Operations
    'advance_salary_approved' => 'Approved the advance salary by :user of amount :amount',
    'advance_salary_rejected' => 'Rejected the advance salary by :user of amount :amount',
    'advance_salary_updated' => 'Updated the advance salary by :user of amount :amount',
    'advance_salary_deleted_amount' => 'Deleted advance salary by :user of amount :amount',

    'absence_approved' => 'Approved absence request',
    'absence_rejected' => 'Rejected absence request',

    'overtime_approved' => 'Approved overtime request',
    'overtime_rejected' => 'Rejected overtime request',

    'checkin_approved' => 'Approved check-in request',
    'checkin_rejected' => 'Rejected check-in request',
];
