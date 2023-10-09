<?php 

return [
    'pdftotext_path' => env('PDFTOTEXT_PATH', ''),
    'pdftsepare_path' => env('PDFSEPARATE_PATH', ''),
    'pdftk_path' => env('PDFTK_PATH', ''),
    'temp_dir' => storage_path('app/public/tmp'),
    'max_payslip_pages' => env('MAXPAYSLIPPAGES', '650'),
    'chunk_size' => env('CHUNK_SIZE', '2'),
    
    'regards' => [
        'company_name' => 'Cible Rh Emploi',
        'additional_text' => 'This is an automatically generated notification. Please do not reply to this e-mail'
    ]
];