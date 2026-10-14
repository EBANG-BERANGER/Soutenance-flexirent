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

    'mtn_momo' => [
        'merchant_number' => env('MTN_MOMO_MERCHANT_NUMBER'),
        'api_key' => env('MTN_MOMO_API_KEY'),
        'api_secret' => env('MTN_MOMO_API_SECRET'),
        // SECURITY: when true, the client's own browser can mark a Mobile
        // Money payment as paid (demo portal, no real money moves). Off by
        // default outside local development; set MOMO_SIMULATION=true only
        // for a supervised demo, then switch it back off.
        'simulation' => (bool) env('MOMO_SIMULATION', env('APP_ENV') === 'local'),
    ],

    'airtel_money' => [
        'merchant_number' => env('AIRTEL_MONEY_MERCHANT_NUMBER'),
        'api_key' => env('AIRTEL_MONEY_API_KEY'),
    ],

];
