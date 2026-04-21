<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'nexah' => [
        'api_url' => env('NEXAH_BASE_URL', 'https://smsvas.com/bulk/public/index.php/api/v1/'),
    ],

    'sns' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_SNS_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
    ],

    'orange_cm' => [
        'application_id' => env('ORANGE_CM_APPLICATION_ID', ''),
        'api_url' => env('ORANGE_CM_API_URL', 'https://api.orange.com'),
        'token_url' => env('ORANGE_CM_TOKEN_URL', 'https://api.orange.com/oauth/v3/token'),
        'token_method' => env('ORANGE_CM_TOKEN_METHOD', 'post'),
        // Messaging Pro Cameroon — POST …/messaging/v1/sms/simple (not /api/v1/…); see getting-started docs
        'sms_endpoint' => env('ORANGE_CM_SMS_ENDPOINT', '/messaging/v1/sms/simple'),
        'msp_auth_url' => env('ORANGE_CM_MSP_AUTH_URL', 'https://api.orange.cm/messaging/api/v1/authenticate'),
        'msp_username' => env('ORANGE_CM_MSP_USERNAME'),
        'msp_password' => env('ORANGE_CM_MSP_PASSWORD'),
        'campaign_title' => env('ORANGE_CM_CAMPAIGN_TITLE', 'Payslip'),
        'campaign_title_max_length' => (int) env('ORANGE_CM_CAMPAIGN_TITLE_MAX_LENGTH', 255),
        // Messaging Pro API: projectName must be 2–8 characters (see Orange 400 validation)
        'project_name' => env('ORANGE_CM_PROJECT_NAME', 'Payroll'),
        'message_max_length' => env('ORANGE_CM_MESSAGE_MAX_LENGTH', 160),
        'contracts_endpoint' => env('ORANGE_CM_CONTRACTS_ENDPOINT', '/sms/admin/v1/contracts'),
        'country' => env('ORANGE_CM_COUNTRY', 'CMR'),
        'default_country_code' => env('ORANGE_CM_DEFAULT_COUNTRY_CODE', '237'),
        'default_sender_address' => env('ORANGE_CM_DEFAULT_SENDER_ADDRESS', ''),
        'http_user_agent' => env('ORANGE_CM_HTTP_USER_AGENT', 'CiblerhPayroll/1.0 (Laravel; Orange SMS)'),
        // api.orange.cm can return CSP headers that break Guzzle's header-line parser; use cURL body-only there.
        'use_php_curl_for_orange_cm_host' => filter_var(env('ORANGE_CM_USE_PHP_CURL_FOR_ORANGE_CM', true), FILTER_VALIDATE_BOOL),
        // If contracts JSON has no recognizable unit fields but you still want to display 0 instead of N/A.
        'trust_zero_balance_from_contracts_api' => filter_var(env('ORANGE_CM_TRUST_ZERO_CONTRACTS_BALANCE', false), FILTER_VALIDATE_BOOL),
    ],
];
