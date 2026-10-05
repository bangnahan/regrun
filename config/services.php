<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'tripay' => [
        'merchant_code' => env('TRIPAY_MERCHANT_CODE', 'T39430'),
        'api_key' => env('TRIPAY_API_KEY', 'DEV-KTItaLxH6EY0VqEkbWrPFgkM8yunO9Btd7bMmNMi'),
        'private_key' => env('TRIPAY_PRIVATE_KEY', 'yNQJm-Ozybz-wRDDa-ncqiY-PZ280'),
        'sandbox' => env('TRIPAY_SANDBOX', true),
    ],

    'mailketing' => [
        'api_token' => env('MAILKETING_API_TOKEN', '308b31d3313311776744479fa8fd7eb3'),
        'sender_email' => env('MAILKETING_SENDER_EMAIL', 'hi@jelatix.com'),
        'sender_name' => env('MAILKETING_SENDER_NAME', 'Panitia Event Lari'),
    ],

];
