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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Payment Providers
    'payment' => [
        'provider' => env('PAYMENT_PROVIDER', 'paystack'),
        'daily_price' => env('SUBSCRIPTION_DAILY_PRICE', 3),
        'monthly_price' => env('SUBSCRIPTION_MONTHLY_PRICE', 60),
    ],

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'callback_url' => env('PAYSTACK_CALLBACK_URL'),
    ],

    'hubtel' => [
        'client_id' => env('HUBTEL_CLIENT_ID'),
        'client_secret' => env('HUBTEL_CLIENT_SECRET'),
        'merchant_account_number' => env('HUBTEL_MERCHANT_ACCOUNT_NUMBER'),
        'api_url' => env('HUBTEL_API_URL'),
        'callback_url' => env('HUBTEL_CALLBACK_URL'),
    ],

    // SMS Provider - Frog SMS API
    'frog_sms' => [
        'api_key' => env('FROG_SMS_API_KEY'),
        'username' => env('FROG_SMS_USERNAME'),
        'sender_id' => env('FROG_SMS_SENDER_ID', 'GhProfit'),
    ],

];
