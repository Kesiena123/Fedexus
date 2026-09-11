<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    */

    'flutterwave' => [
        'base_url' => env('FLUTTERWAVE_API_URL'),
        'guest_email' => env('FLUTTERWAVE_GUEST_EMAIL'),
    ],

    'stripe' => [
        'base_url' => env('STRIPE_API_URL'),
    ],

    'paypal' => [
        'sandbox_base_url' => env('PAYPAL_SANDBOX_URL'),
        'live_base_url' => env('PAYPAL_LIVE_URL'),
    ],

    'nowpayments' => [
        'base_url' => env('NOWPAYMENTS_API_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Geocoding / Map Configuration
    |--------------------------------------------------------------------------
    */

    'geocoding' => [
        'nominatim_url' => env('NOMINATIM_URL'),
        'user_agent' => env('GEOCODING_USER_AGENT'),
    ],

];
