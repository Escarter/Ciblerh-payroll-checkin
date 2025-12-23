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
    'companies_imported' => 'Companies Imported',
    'companies_import_failed' => 'Companies Import Failed',

    // Department actions
    'department_created' => 'Department Created',
    'department_updated' => 'Department Updated',
    'department_deleted' => 'Department Deleted',
    'departments_imported' => 'Departments Imported',
    'departments_import_failed' => 'Departments Import Failed',

    // Service actions
    'service_created' => 'Service Created',
    'service_updated' => 'Service Updated',
    'service_deleted' => 'Service Deleted',
    'service_force_deleted' => 'Service Force Deleted',
    'services_imported' => 'Services Imported',
    'services_import_failed' => 'Services Import Failed',

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
    'cancel_payslip_process' => 'Cancel Payslip Process',
    'send_sms' => 'Send SMS',
    'send_email' => 'Send Email',
    'employee_exported' => 'Employee Exported',
    'employees_exported' => 'Employees Exported',
    'employees_imported' => 'Employees Imported',
    'employees_import_failed' => 'Employees Import Failed',
    'service_exported' => 'Service Exported',
    'department_exported' => 'Department Exported',

    // Leave type actions
    'leave_type_created' => 'Leave Type Created',
    'leave_type_updated' => 'Leave Type Updated',
    'leave_type_deleted' => 'Leave Type Deleted',
    'leave_type_force_deleted' => 'Leave Type Force Deleted',
    'leave_type_imported' => 'Leave Type Imported',
    'leave_type_exported' => 'Leave Type Exported',
    'leave_types_imported' => 'Leave Types Imported',
    'leave_types_import_failed' => 'Leave Types Import Failed',
    
    // Leave actions
    'leave_created' => 'Leave Created',
    'leave_updated' => 'Leave Updated',
    'leave_deleted' => 'Leave Deleted',
    'leave_force_deleted' => 'Leave Force Deleted',
    'leave_approved' => 'Leave Approved',
    'leave_rejected' => 'Leave Rejected',

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
    'role_force_deleted' => 'Role Force Deleted',

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
    'bulk_delete_payslip_process_for' => 'Bulk delete Payslip process for :month-:year @ :time',
    'bulk_permanently_delete_payslip_process_for' => 'Bulk permanently delete Payslip process for :month-:year @ :time',
    'cancel_payslip_process_for' => 'Cancelled payslip process for :month-:year @ :time',
    'payslip_sending_initiated' => 'User :user initiated the sending of payslip to department :department for the month of :month-:year :history_link',
    'send_email_to_employee' => 'User :user sent email to :employee',
    'send_sms_to_employee' => 'User :user sent SMS to :employee',
    'exported_overtime' => 'Exported excel file for overtime',
    'exported_employees_for_company' => 'Exported excel file for employees for company :company',
    'exported_services_for_department' => 'Exported excel file for services for department :department',
    'exported_departments_for_company' => 'Exported excel file for departments for company :company',
    'report_generated_for_payslips' => ':user generated report for payslips',

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

    // Bulk Operations
    'bulk_approved_absences' => 'Bulk approved :count absence(s)',
    'bulk_rejected_absences' => 'Bulk rejected :count absence(s)',
    'bulk_approved_overtimes' => 'Bulk approved :count overtime(s)',
    'bulk_rejected_overtimes' => 'Bulk rejected :count overtime(s)',
    'bulk_approved_leaves' => 'Bulk approved :count leave(s)',
    'bulk_rejected_leaves' => 'Bulk rejected :count leave(s)',
    'bulk_approved_advance_salaries' => 'Bulk approved :count advance salary(ies)',
    'bulk_rejected_advance_salaries' => 'Bulk rejected :count advance salary(ies)',
    'bulk_approved_checklogs' => 'Bulk approved :count checklog(s)',
    'bulk_rejected_checklogs' => 'Bulk rejected :count checklog(s)',

    // Audit Log Permissions
    'view_own_logs_only' => 'View own logs only',
    'read_all' => 'Read All',
    'read_own_only' => 'Read Own Only',
    'audit_log' => 'Audit Log',
    
    // Audit Log Management Messages
    'audit_log_not_found' => 'Audit log not found',
    'audit_log_moved_to_trash' => 'Audit log successfully moved to trash',
    'audit_log_permanently_deleted' => 'Audit log permanently deleted',
    'audit_log_restored' => 'Audit log successfully restored',
    'selected_audit_logs_moved_to_trash' => 'Selected audit logs successfully moved to trash',
    'selected_audit_logs_restored' => 'Selected audit logs successfully restored!',
    'selected_audit_logs_permanently_deleted' => 'Selected audit logs permanently deleted!',
    'danger_deleting_audit_log' => 'Error deleting audit log: ',

    // Action Filter Options
    'action_created' => 'Created',
    'action_updated' => 'Updated',
    'action_deleted' => 'Deleted',
    'action_login' => 'Login',
    'action_logout' => 'Logout',
    'action_exported' => 'Exported',
    'action_imported' => 'Imported',

    // Detail Modal
    'log_details' => 'Log Details',
    'basic_information' => 'Basic Information',
    'timestamp_info' => 'Timestamp Information',
    'system' => 'System',
    'model_information' => 'Model Information',
    'model_type' => 'Model Type',
    'model_id' => 'Model ID',
    'model_name' => 'Model Name',
    'changes' => 'Changes',
    'field_changes' => 'field changes',
    'field' => 'Field',
    'old_value' => 'Old Value',
    'new_value' => 'New Value',
    'metadata' => 'Metadata',
    'ip_address' => 'IP Address',
    'url' => 'URL',
    'method' => 'HTTP Method',
    'n_a' => 'N/A',
    'logs_list' => 'Logs List',
    'total_logs_lowercase' => 'total logs',
    'no_logs_found' => 'No logs found',
    'try_adjusting_filters' => 'Try adjusting your filters',
    'description' => 'Description',
    
    // Table Headers
    'user' => 'User',
    'action' => 'Action',
    'model' => 'Model',
    'date' => 'Date',
    'actions' => 'Actions',
    
    // Title and Description
    'title' => 'Audit Logs',
    'description_page' => 'View and manage all system activity logs',
];
