<?php 

return [
    'pdftotext_path' => env('PDFTOTEXT_PATH', ''),
    'pdftsepare_path' => env('PDFSEPARATE_PATH', ''),
    'pdftk_path' => env('PDFTK_PATH', ''),
    'temp_dir' => storage_path('app/public/tmp'),
    'max_payslip_pages' => env('MAXPAYSLIPPAGES', '650'),
    'chunk_size' => env('CHUNK_SIZE', '50'),
    'email_retry_attempts' => env('EMAIL_RETRY_ATTEMPTS', 3), // Number of automatic retry attempts for failed emails
    'email_retry_delay' => env('EMAIL_RETRY_DELAY', 60), // Delay in seconds between retry attempts
    
    'regards' => [
        'company_name' => env('COMPANY_SUPPORT_EMAIL', 'WiMa HR'),
        'company_support_email' => env('COMPANY_SUPPORT_EMAIL','support@wima.com'),
        'additional_text' => 'This is an automatically generated notification. Please do not reply to this e-mail'
    ]
];

