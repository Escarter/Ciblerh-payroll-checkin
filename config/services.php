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
        'sms_endpoint' => env('ORANGE_CM_SMS_ENDPOINT', '/smsmessaging/v1/outbound/{senderAddress}/requests'),
        'contracts_endpoint' => env('ORANGE_CM_CONTRACTS_ENDPOINT', '/sms/admin/v1/contracts'),
        'country' => env('ORANGE_CM_COUNTRY', 'CMR'),
        'default_country_code' => env('ORANGE_CM_DEFAULT_COUNTRY_CODE', '237'),
        'default_sender_address' => env('ORANGE_CM_DEFAULT_SENDER_ADDRESS', ''),
    ],
];
