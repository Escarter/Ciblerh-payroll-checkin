<?php 

return [
    'pdftotext_path' => env('PDFTOTEXT_PATH', ''),
    'pdftsepare_path' => env('PDFSEPARATE_PATH', ''),
    'pdftk_path' => env('PDFTK_PATH', ''),
    'temp_dir' => storage_path('app/public/tmp'),
    'max_payslip_pages' => env('MAXPAYSLIPPAGES', '650'),
    'chunk_size' => env('CHUNK_SIZE', '50'),
    
    'regards' => [
        'company_name' => env('COMPANY_SUPPORT_EMAIL', 'WiMa HR'),
        'company_support_email' => env('COMPANY_SUPPORT_EMAIL','support@wima.com'),
        'additional_text' => 'This is an automatically generated notification. Please do not reply to this e-mail'
    ]
];