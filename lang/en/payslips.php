<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payslips Management Language Lines
    |--------------------------------------------------------------------------
    */

    'payslips' => 'Payslips',
    'payslip' => 'Payslip',
    'total_payslips' => 'Total Payslips',
    'payslip_process' => 'Payslip Process',
    'payslip_processes' => 'Payslip Processes',
    'total_processes' => 'Total Processes',

    // Payslip actions
    'download_payslip' => 'Download Payslip',
    'resend_payslip' => 'Resend Payslip',
    'view_details_download_payslips' => 'View Details & Download Payslips',
    'resend_email' => 'Resend Email',
    'resend_sms' => 'Resend SMS',
    'payslip_not_found' => 'Payslip not found.',
    'error_deleting_payslip' => 'Error deleting payslip: ',

    // Process management
    'delete_payslip_process' => 'Delete Payslip process for ',
    'payslip_process_moved_to_trash' => 'Payslip Process successfully moved to trash!',
    'payslip_process_restored' => 'Payslip Process successfully restored!',
    'permanently_delete_payslip_process' => 'Permanently delete Payslip process for ',
    'payslip_process_permanently_deleted' => 'Payslip Process permanently deleted!',
    'bulk_delete_payslip_process' => 'Bulk delete Payslip process for ',
    'selected_payslip_processes_moved_to_trash' => 'Selected payslip processes successfully moved to trash!',
    'selected_payslip_processes_restored' => 'Selected payslip processes successfully restored!',
    'bulk_permanently_delete_payslip_process' => 'Bulk permanently delete Payslip process for ',
    'selected_payslip_processes_permanently_deleted' => 'Selected payslip processes permanently deleted!',

    // Bulk operations
    'employee_payslip_resent_successfully' => 'Employee Payslip resent successfully',
    'email_resent_successfully' => 'Email resent successfully!',
    'failed_to_resent_email' => 'Failed to resent Email',
    'sms_sent_successfully' => 'SMS to :user successfully sent!',
    'insufficient_sms_balance' => 'Insufficient SMS Balance',

    // Process status
    'successful' => 'Successful',
    'failed' => 'Failed',
    'pending' => 'Pending',
    'processing' => 'Processing...',
    'disabled' => 'Disabled',

    // Process results
    'process_completed_with_failures' => 'Process completed with :failed out of :total payslips failed to send',
    'unmatched_employees' => ':unmatched out of :total employees could not be matched to PDF files',
    'unmatched_employees_title' => 'Unmatched Employees',
    'unmatched_employees_description' => 'Employees whose payslips could not be found in the PDF files',
    'no_unmatched_employees_found' => 'No unmatched employees found',
    'view_unmatched_employees' => 'View Unmatched Employees',

    // Settings and requirements
    'smtp_setting_required' => 'Setting for SMTP required!!',
    'sms_setting_required' => 'Setting for SMS required!',
    'sms_smtp_settings_required' => 'Setting for SMS and SMTP configurations required!!',
    'insufficient_sms_balance_refill' => 'SMS Balance is not enough, Refill SMS to proceed',
    'file_upload_page_limit' => 'File uploaded needs to have ',
    'file_upload_page_limit_suffix' => ' pages maximum',
    'job_processing_status' => 'Job started to process list and file uploaded check the status on the table!',

    // Encryption and sending
    'encryption_failed_email_sms_skipped' => 'Email/SMS skipped: Encryption failed. ',
    'failed_to_send_email_recipient' => 'Failed to send email. Recipient: :email',
    'email_error' => 'Email error: :error',
    'email_rfc_error' => 'Email RFC error: :error',
    'no_valid_email_address' => 'No valid email address for User',
    'no_errors' => 'No errors',
    'failure_reason' => 'Failure Reason',

    // Retry logic
    'failed_to_send_email_retry_scheduled' => 'Failed to send email. Recipient: :email. Retry :retry/:max scheduled',
    'failed_to_send_email_after_max_retries' => 'Failed to send email after :max retry attempts. Recipient: :email',
    'email_error_retry_scheduled' => 'Email error: :error. Retry :retry/:max scheduled',
    'email_error_after_max_retries' => 'Email error after :max retries: :error',
    'email_rfc_error_retry_scheduled' => 'Email RFC error: :error. Retry :retry/:max scheduled',
    'email_rfc_error_after_max_retries' => 'Email RFC error after :max retries: :error',
    'retry_failed_payslip_not_found' => 'Retry failed: Payslip file not found',
    'retry_failed_no_valid_email' => 'Retry failed: No valid email address',
    'retry_attempt_failed_email_delivery' => 'Retry attempt failed: Email delivery failed',
    'retry_attempt_failed_with_next' => 'Retry attempt :retry failed: Email delivery failed. Retry :next/:max scheduled',
    'retry_attempt_failed_after_max' => 'Retry attempt failed after :max retries: Email delivery failed',
    'retry_error' => 'Retry error: :error',
    'retry_rfc_error' => 'Retry RFC error: :error',
    'email_automatic_retry_scheduled' => 'Failed to send email. Automatic retry scheduled.',
    'email_automatic_retry_scheduled_if_enabled' => 'Failed to send email. Automatic retry scheduled if enabled.',
    'email_notifications_disabled' => 'Email notifications disabled for this employee',

    // Email bounce handling
    'email_previously_bounced' => 'Email previously bounced: :reason',
    'email_bounced_update_address' => 'Email address has bounced previously. Please update employee email address.',
    'email_bounced' => 'Email bounced: :reason',
    'email_invalid_or_does_not_exist' => 'Email address is invalid or does not exist',

    // Bulk resend
    'resend_all_failed' => 'Resend All Failed',
    'confirm_resend_all_failed' => 'Are you sure you want to resend all failed payslips?',
    'resend_all_failed_count' => 'This will attempt to resend :count failed payslips.',
    'bulk_resend_completed' => 'Bulk resend completed: :resend successful, :skipped skipped',
    'no_failed_payslips_to_resend' => 'No failed payslips found to resend.',

    // Failure reasons
    'no_valid_email_address' => 'No valid email address for User',
    'failed_sending_email_sms' => 'Failed sending Email & SMS',
    'failed_to_resent_email' => 'Failed to resent Email',
];
