<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Import Jobs Language Lines
    |--------------------------------------------------------------------------
    */

    'manage_import_jobs' => 'Manage your import jobs and track their progress',
    'job_details' => 'Import Job Details',
    'no_jobs_found' => 'No import jobs found',
    'no_jobs_message' => 'You haven\'t started any import jobs yet',
    'confirm_cancel_job' => 'Are you sure you want to cancel this import job?',
    'confirm_bulk_cancel' => 'Are you sure you want to cancel the selected import jobs?',
    'job_cancelled_successfully' => 'Import job cancelled successfully',
    'unable_to_cancel_job' => 'Unable to cancel the import job',
    'jobs_cancelled_successfully' => 'Successfully cancelled :count import job(s)',
    'please_select_jobs_to_cancel' => 'Please select import jobs to cancel',
    'jobs_refreshed_successfully' => 'Import jobs refreshed successfully',
    'job_cannot_be_cancelled' => 'This import job cannot be cancelled',
    'progress_statistics' => 'Progress Statistics',
    'create_new_import' => 'Create New Import',
    'create_import_description' => 'Upload a file and select import type to start importing data',
    'import_job_created_successfully' => 'Import job created successfully',
    'error_creating_import_job' => 'Error creating import job',
    'start_import' => 'Start Import',
    'import_configuration' => 'Import Configuration',
    'download_template_description' => 'Download a template file to ensure your data is formatted correctly',

    'create_import_modal_title' => 'Create Import',
    'create_import' => 'Create Import',
    // Background import completion messages
    'background_import_completed' => 'Background import completed successfully. :count records imported.',
    'name' => 'Import Job',

    // Notification messages
    'import_completed_subject' => ':type Import Completed',
    'import_completed_greeting' => 'Hello :name,',
    'import_completed_message' => 'Your :type import has been completed. Total records: :total, Successful: :successful, Failed: :failed.',
    'import_completed_with_errors' => 'Some records failed to import. Please check the import details for more information.',
    'import_completed_notification' => ':type import completed: :successful of :total records imported successfully.',

    'import_failed_subject' => ':type Import Failed',
    'import_failed_greeting' => 'Hello :name,',
    'import_failed_message' => 'Your :type import has failed with the following error: :error',
    'import_failed_notification' => ':type import failed. Please check the import details.',

    // Import result messages
    'import_background_success' => 'Background import completed successfully for :type. :count records imported.',
    'import_background_failed' => 'Background import failed for :type. Error: :error',
    'import_with_errors' => 'with :errors errors',
    'import_partial_failures' => '(:failed records failed)',
    'import_failed_detailed' => 'Import failed: :error',

    // Toast notification messages
    'import_completed_title' => 'Import Completed',
    'import_failed_title' => 'Import Failed',
    'import_completed_toast' => ':type import completed: :successful of :total records imported successfully.',
    'import_failed_toast' => ':type import failed: :error',
    'import_with_errors_toast' => '(:errors records had errors)',

    // Bulk actions
    'bulk_delete' => 'Delete Selected',
    'bulk_restore' => 'Restore Selected',
    'bulk_cancel' => 'Cancel Selected',
    'bulk_retry' => 'Retry Selected',
    'confirm_bulk_delete' => 'Are you sure you want to permanently delete the selected import jobs? This action cannot be undone.',
    'confirm_bulk_restore' => 'Are you sure you want to restore the selected import jobs?',
    'confirm_bulk_retry' => 'Are you sure you want to retry the selected failed import jobs?',
    'jobs_deleted_successfully' => 'Successfully deleted :count import job(s)',
    'jobs_restored_successfully' => 'Successfully restored :count import job(s)',
    'jobs_retried_successfully' => 'Successfully retried :count import job(s)',
    'please_select_jobs_to_delete' => 'Please select import jobs to delete',
    'please_select_jobs_to_restore' => 'Please select import jobs to restore',
    'please_select_jobs_to_retry' => 'Please select failed import jobs to retry',

    // Individual actions
    'delete_job' => 'Delete Import Job',
    'restore_job' => 'Restore Import Job',
    'retry_job' => 'Retry Import Job',
    'confirm_delete_job' => 'Are you sure you want to permanently delete this import job? This action cannot be undone.',
    'confirm_restore_job' => 'Are you sure you want to restore this import job?',
    'confirm_retry_job' => 'Are you sure you want to retry this failed import job?',
    'job_deleted_successfully' => 'Import job deleted successfully',
    'job_deleted_permanently' => 'Import job permanently deleted',
    'jobs_deleted_permanently' => 'Successfully permanently deleted :count import job(s)',
    'job_moved_to_trash_successfully' => 'Import job moved to trash successfully',
    'jobs_moved_to_trash_successfully' => 'Successfully moved :count import job(s) to trash',
    'job_restored_successfully' => 'Import job restored successfully',
    'job_retried_successfully' => 'Import job retried successfully',
    'error_retrying_job' => 'Error retrying import job',
    'can_only_retry_failed_jobs' => 'Only failed import jobs can be retried',
    'original_file_not_found' => 'Original file not found. Cannot retry import job.',
    'job_not_found' => 'Import job not found',

    // Retry modal messages
    'retry_job_confirmation_title' => 'Retry Import Job',
    'restore_job_confirmation_title' => 'Restore Import Job',
    'retry_job_confirmation_message' => 'Are you sure you want to retry this failed import job? This will create a new import job with the same settings.',
    'bulk_retry_confirmation_title' => 'Retry Selected Jobs',
    'bulk_retry_confirmation_message' => 'Are you sure you want to retry :count failed import job(s)? This will create new import jobs with the same settings.',
    'bulk_retry_confirm' => 'Retry Jobs',

    // UI elements
    'filters_and_search' => 'Filters & Search',
    'clear_all' => 'Clear All',
    'all_import_types' => 'All Import Types',
    'select_all' => 'Select All',
    'deselect_all' => 'Deselect All',
    'selected' => 'selected',
    'move_to_trash' => 'Move to Trash',
    'delete_forever' => 'Delete Forever',
    'restore_selected' => 'Restore Selected',
    'total_import_jobs' => 'Total Import Jobs',
    'manage_import_jobs_details' => 'Create, monitor, and manage your data import operations',

    // Preview step
    'preview_data_step' => 'Preview Import Data',
    'preview_data_description' => 'Review a sample of your data before proceeding with the import.',

    // Preview messages
    'no_preview_data_available' => 'Preview Not Processed Yet',
    'preview_data_unavailable_message' => 'Click the button below to analyze your file and generate a preview of the data that will be imported.',

    // Step subtitles
    'create_import_subtitle' => 'Configure your import settings and upload your file',
    'preview_subtitle' => 'Review and validate your data before importing',
    'confirm_subtitle' => 'Final review and start the import process',

    // Confirm step
    'confirm_import_step' => 'Confirm Import',
    'confirm_import_description' => 'Review your import settings and start the import process.',

    // Missing UI elements
    'view_details' => 'View Details',
    'cancel_job' => 'Cancel Job',
    'cancel' => 'Cancel',
    'unknown_import_type' => 'Unknown Import Type',
];