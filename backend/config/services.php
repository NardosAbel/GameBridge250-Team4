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

    /*
    | Payments stay fail-closed. Sandbox may create a local checkout reference
    | for tests and local demos. It never marks a webhook valid by itself.
    | Production must set PAYMENTS_SANDBOX=false and real webhook secrets.
    */
    'payments' => [
        'sandbox' => env('PAYMENTS_SANDBOX', false),
        'providers' => [
            'telebirr' => [
                'merchant_id' => env('TELEBIRR_APP_ID'),
                'webhook_secret' => env('TELEBIRR_WEBHOOK_SECRET'),
            ],
            'chapa' => [
                'merchant_id' => env('CHAPA_SECRET_KEY'),
                'webhook_secret' => env('CHAPA_WEBHOOK_SECRET'),
            ],
            'mpesa' => [
                'merchant_id' => env('MPESA_SHORTCODE'),
                'webhook_secret' => env('MPESA_WEBHOOK_SECRET'),
            ],
            'card' => [
                'merchant_id' => env('CARD_MERCHANT_ID'),
                'webhook_secret' => env('CARD_WEBHOOK_SECRET'),
            ],
            'bank_transfer' => [
                'merchant_id' => env('BANK_TRANSFER_ACCOUNT_NAME'),
                'webhook_secret' => env('BANK_TRANSFER_WEBHOOK_SECRET'),
                'accounts' => array_values(array_filter([
                    env('BANK_TRANSFER_ACCOUNT'),
                ])),
            ],
            'manual' => [
                'merchant_id' => env('MANUAL_PAYMENT_DESK'),
                'webhook_secret' => env('MANUAL_PAYMENT_WEBHOOK_SECRET'),
            ],
        ],
    ],

];
