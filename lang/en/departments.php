<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Departments Management Language Lines
    |--------------------------------------------------------------------------
    */

    'departments' => 'Departments',
    'department' => 'Department',
    'departments_short' => 'Depts',
    'total_departments' => 'Total Departments',
    'create_department' => 'Create Department',
    'edit_department' => 'Edit Department',
    'department_created_successfully' => 'Department created successfully!',
    'department_updated_successfully' => 'Department updated successfully!',
    'department_deleted_successfully' => 'Department deleted!',
    'department_restored_successfully' => 'Department successfully restored!',
    'department_permanently_deleted' => 'Department permanently deleted!',
    'selected_departments_moved_to_trash' => 'Selected departments moved to trash!',
    'selected_departments_restored' => 'Selected departments restored!',
    'selected_departments_permanently_deleted' => 'Selected departments permanently deleted!',

    // Department fields
    'name' => 'Name',
    'company' => 'Company',
    'supervisor' => 'Supervisor',
    'supervisor_email' => 'Supervisor Email',
    'is_active' => 'Is Active?',
    'select_supervisor' => 'Select supervisor',
    'select_status' => 'Select status',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'unknown_company' => 'Unknown Company',
    'departments_created_in_assigned_companies' => 'Departments will be created in your assigned companies',

    // Import/Export
    'department_required_for_service_import' => 'Department is required for service import',
    'department_belongs_to_company' => 'Department must belong to a company for service import',
    'department_context_required' => 'Department context is required for service import',
    'company_required_for_department_import' => 'Company is required for department import',
    'supervisor_email_not_found' => 'Supervisor email not found',
    'company_context_required_for_department_import' => 'Company context is required for department import',
    'supervisor_email_empty' => 'Supervisor email is empty',
    'supervisor_not_found' => 'Supervisor with email ":email" not found',
    'you_can_now_specify_supervisor_emails_and_company_names_directly_in_your_import_file' => 'You can now specify supervisor emails and company names directly in your import file.',
    'preview_your_data_before_importing_to_ensure_accuracy' => 'Preview your data before importing to ensure accuracy.',
    'import_completed' => 'Import completed successfully',

    // Permissions
    'cannot_permanently_delete_department' => 'Cannot permanently delete department. It has related records.',
    'cannot_permanently_delete_departments' => 'Cannot permanently delete the following departments as they have related records: ',
    'related_records_protection' => 'If this item has related records, the deletion will be prevented to maintain data integrity.',
    'supervisors_cannot_create' => 'Supervisors cannot create new departments.',
    'supervisor_successfully_assigned' => 'Supervisor successfully assigned!',
    'department_successfully_moved_to_trash' => 'Department successfully moved to trash!',
    'department_successfully_restored' => 'Department successfully restored!',
    'selected_departments_moved_to_trash' => 'Selected departments moved to trash!',
    'selected_departments_restored' => 'Selected departments restored!',
    'selected_departments_permanently_deleted' => 'Selected departments permanently deleted!',
    'departments_successfully_uploaded' => 'Departments successfully uploaded!',
    'imported_excel_file_for_departments' => 'Imported excel file for departments for company ',
    'exported_excel_file_for_departments' => 'Exported excel file for departments for company ',
    'my_departments' => 'My Departments',
    'departments_management' => 'Departments Management',
    'manage_departments_details' => 'Manage departments and their related details',
    'assign_supervisor' => 'Assign Supervisor',
    'assign_supervisor_to_department' => 'Assign supervisor to manage department employees',
    'assign' => 'Assign',
    'no_assigned_supervisor' => 'No assigned supervisor',
    'for_these_companies' => 'for these companies',
    'in_this_company' => 'in this company',
    'that_are_active' => 'that are active!',
    'per_page' => 'Per Page',
    'select_all' => 'Select All',
    'deselect_all' => 'Deselect All',
    'create_new_department_to_manage' => 'Create a new Department to manage',
    'multiple_companies' => 'Multiple Companies',
    'departments_will_be_created_in_assigned_companies' => 'Departments will be created in your assigned companies',
    'select_supervisor' => 'Select supervisor',
    'select_department' => 'Select',

    // Success Messages
    'department_created_successfully' => 'Department created successfully!',
    'department_successfully_updated' => 'Department successfully updated!',
    'department_successfully_moved_to_trash' => 'Department successfully moved to trash!',
    'department_successfully_restored' => 'Department successfully restored!',
    'department_permanently_deleted' => 'Department permanently deleted!',
    'supervisor_successfully_assigned' => 'Supervisor successfully assigned!',
    'selected_departments_moved_to_trash' => 'Selected departments moved to trash!',
    'selected_departments_restored' => 'Selected departments restored!',
    'selected_departments_permanently_deleted' => 'Selected departments permanently deleted!',
    'departments_successfully_uploaded' => 'Departments successfully uploaded!',

    // Error Messages
    'supervisors_cannot_create' => 'Supervisors cannot create new departments.',
    'cannot_permanently_delete_department' => 'Cannot permanently delete department. It has related records.',
    'cannot_permanently_delete_departments' => 'Cannot permanently delete the following departments as they have related records: ',

    // Import/Export Messages
    'imported_excel_file_for_departments' => 'Imported excel file for departments for company ',
    'exported_excel_file_for_departments' => 'Exported excel file for departments for company ',

    // UI Labels
    'edit_department_details' => 'Edit and update department details',
    'create_department_to_manage' => 'Create a new department to manage',
    'is_active' => 'Is Active',
    'inactive' => 'Inactive',
    'that_are_deleted' => 'that are deleted!',

    // Table Headers
    'id' => 'ID',
    'staff' => 'Staff',
    'services' => 'Services',
    'employees' => 'Employees',

    // Button Titles
    'view_services' => 'View Services',
    'manage_employees' => 'Manage Employees',
    'edit_department' => 'Edit Department',
    'restore_department' => 'Restore Department',
    'permanently_delete' => 'Permanently Delete',
    'add_department' => 'Add Department',

    // Form Labels
    'order' => 'Order',

    // Bulk Operations
    'restore_selected_departments' => 'Restore Selected Departments',
    'permanently_delete_selected_departments' => 'Permanently Delete Selected Departments',

    // Import validation keys
    'department_required' => 'Department is required',
    'name_required' => 'Department name is required',
    'name_is_required' => 'Department name is required',
    'name_must_be_text' => 'Department name must be text',
    'name_cannot_exceed_255_characters' => 'Department name cannot exceed 255 characters',
    'name_already_exists' => 'Department name already exists in this company',
    'supervisor_email_required' => 'Supervisor email is required when assigning a supervisor',
    'supervisor_not_found' => 'Supervisor not found with the provided email',
    'supervisor_wrong_company' => 'Supervisor does not belong to the same company',
    'supervisor_wrong_role' => 'User must have supervisor role to be assigned as department supervisor',

    // Background import completion messages
    'background_import_completed' => 'Background import completed successfully. :count records imported.',

    // SMS Notifications
    'bulk_sms_department_label' => 'Department-wide SMS notifications',
    'bulk_sms_select_department' => 'Select Department',
    'bulk_sms_department_help' => 'Disable or enable SMS notifications for all employees in a department',
    'bulk_sms_disable_action' => 'Disable SMS for department',
    'bulk_sms_enable_action' => 'Enable SMS for department',
    'bulk_sms_select_department_first' => 'Please select a department first',
    'bulk_sms_department_not_found' => 'Department not found',
    'bulk_sms_department_role_not_allowed' => 'You do not have permission to manage SMS notifications',
    'bulk_sms_disabled_for_department' => 'SMS notifications disabled for :count employees in :department',
    'bulk_sms_enabled_for_department' => 'SMS notifications enabled for :count employees in :department',
    'bulk_sms_disable_confirm' => 'Are you sure you want to disable SMS notifications?',
    'bulk_sms_enable_confirm' => 'Are you sure you want to enable SMS notifications?',
    'bulk_sms_disable_confirm_department_name' => 'Are you sure you want to disable SMS notifications for all employees in :department?',
    'bulk_sms_enable_confirm_department_name' => 'Are you sure you want to enable SMS notifications for all employees in :department?',
    'bulk_email_department_help' => 'Disable or enable email notifications for all employees in a department',
    'bulk_email_disable_action' => 'Disable email for department',
    'bulk_email_enable_action' => 'Enable email for department',
    'bulk_email_disabled_for_department' => 'Email notifications disabled for :count employees in :department',
    'bulk_email_enabled_for_department' => 'Email notifications enabled for :count employees in :department',
    'manage_notifications_action' => 'Manage notifications',
    'manage_notifications_title' => 'Manage department notifications',
    'manage_notifications_help' => 'Choose a department, then enable or disable SMS and email notifications for its employees.',
];
