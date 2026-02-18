<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Payment settings
            [
                'key' => 'payment_provider',
                'value' => env('PAYMENT_PROVIDER', 'hubtel'),
                'type' => 'string',
                'group' => 'payment',
                'description' => 'Active payment provider (hubtel or paystack)',
            ],
            [
                'key' => 'daily_price',
                'value' => '3',
                'type' => 'float',
                'group' => 'payment',
                'description' => 'Daily subscription price in GHC',
            ],
            [
                'key' => 'monthly_price',
                'value' => '60',
                'type' => 'float',
                'group' => 'payment',
                'description' => 'Monthly subscription price in GHC',
            ],
            [
                'key' => 'service_charge_percentage',
                'value' => '2',
                'type' => 'float',
                'group' => 'payment',
                'description' => 'Service charge percentage added to every transaction',
            ],

            // Hubtel credentials
            [
                'key' => 'hubtel_client_id',
                'value' => env('HUBTEL_CLIENT_ID', ''),
                'type' => 'string',
                'group' => 'hubtel',
                'description' => 'Hubtel API Client ID',
            ],
            [
                'key' => 'hubtel_client_secret',
                'value' => env('HUBTEL_CLIENT_SECRET', ''),
                'type' => 'string',
                'group' => 'hubtel',
                'description' => 'Hubtel API Client Secret',
            ],
            [
                'key' => 'hubtel_merchant_account_number',
                'value' => env('HUBTEL_MERCHANT_ACCOUNT_NUMBER', ''),
                'type' => 'string',
                'group' => 'hubtel',
                'description' => 'Hubtel Merchant Account Number',
            ],
            [
                'key' => 'hubtel_api_url',
                'value' => env('HUBTEL_API_URL', 'https://payproxyapi.hubtel.com/items/initiate'),
                'type' => 'string',
                'group' => 'hubtel',
                'description' => 'Hubtel Payment API URL',
            ],
            [
                'key' => 'hubtel_callback_url',
                'value' => env('HUBTEL_CALLBACK_URL', ''),
                'type' => 'string',
                'group' => 'hubtel',
                'description' => 'Hubtel payment callback URL',
            ],

            // Paystack credentials
            [
                'key' => 'paystack_public_key',
                'value' => env('PAYSTACK_PUBLIC_KEY', ''),
                'type' => 'string',
                'group' => 'paystack',
                'description' => 'Paystack Public Key',
            ],
            [
                'key' => 'paystack_secret_key',
                'value' => env('PAYSTACK_SECRET_KEY', ''),
                'type' => 'string',
                'group' => 'paystack',
                'description' => 'Paystack Secret Key',
            ],

            // SMS settings
            [
                'key' => 'welcome_sms_message',
                'value' => 'Welcome to GhLinks! Login at starlink.ghprofit.com using your phone number and OTP.',
                'type' => 'string',
                'group' => 'sms',
                'description' => 'SMS message sent to new users when added to the system',
            ],
            [
                'key' => 'reminder_sms_message',
                'value' => 'Hi {name}, your GhLinks subscription expires tomorrow. Please renew at starlink.ghprofit.com',
                'type' => 'string',
                'group' => 'sms',
                'description' => 'SMS reminder sent before subscription expires. Use {name} for user name.',
            ],

            // General settings
            [
                'key' => 'app_domain',
                'value' => 'starlink.ghprofit.com',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Main application domain shown in SMS messages',
            ],
            [
                'key' => 'app_name',
                'value' => 'GhLinks',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Application display name',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
