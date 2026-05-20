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

    // Orange Cameroun — Business Messaging Ngage API (see "MODOP … Interface API SMS Orange Cameroun v1").
    'orange_cm' => [
        // Authenticate: POST {email, password} -> {access_token}
        'login_url' => env('ORANGE_CM_LOGIN_URL', 'https://businessmessaging.orange.cm/api/v1/accounts/users/login'),
        // Send simple (instant) SMS — Bearer access_token
        'send_url' => env('ORANGE_CM_SEND_URL', 'https://businessmessaging.orange.cm/api/v1/sms/send'),
        // Send scheduled campaign SMS (not used for payslips; here for reference/completeness)
        'schedule_url' => env('ORANGE_CM_SCHEDULE_URL', 'https://businessmessaging.orange.cm/api/v1/campaigns/sms/send'),
        // Mandatory send body fields with sensible defaults (case-sensitive on Orange's side).
        'category' => env('ORANGE_CM_CATEGORY', 'Promo'),
        'country' => env('ORANGE_CM_COUNTRY', 'CM'),
        // Optional delivery-report callback URL. When empty, the app's own webhook endpoint is used (see dr_callback_auto).
        'dr_callback' => env('ORANGE_CM_DR_CALLBACK', ''),
        // When no dr_callback is configured, auto-build it from APP_URL + the webhooks.orange-sms.dr route.
        'dr_callback_auto' => filter_var(env('ORANGE_CM_DR_CALLBACK_AUTO', true), FILTER_VALIDATE_BOOL),
        // Shared secret appended to (and verified on) the DR callback URL. Strongly recommended.
        'dr_callback_token' => env('ORANGE_CM_DR_CALLBACK_TOKEN', ''),
        'message_max_length' => env('ORANGE_CM_MESSAGE_MAX_LENGTH', 160),
        'default_country_code' => env('ORANGE_CM_DEFAULT_COUNTRY_CODE', '237'),
        // Fallback sender if none is configured in SMS settings.
        'default_sender' => env('ORANGE_CM_DEFAULT_SENDER', ''),
        // Token validity per the spec is 3600s (1h).
        'token_ttl' => (int) env('ORANGE_CM_TOKEN_TTL', 3600),
        'http_user_agent' => env('ORANGE_CM_HTTP_USER_AGENT', 'CiblerhPayroll/1.0 (Laravel; Orange SMS)'),
        // Orange edges can return CSP headers that break Guzzle's header-line parser; use cURL body-only there.
        'use_php_curl_for_orange_cm_host' => filter_var(env('ORANGE_CM_USE_PHP_CURL_FOR_ORANGE_CM', true), FILTER_VALIDATE_BOOL),
    ],
];
