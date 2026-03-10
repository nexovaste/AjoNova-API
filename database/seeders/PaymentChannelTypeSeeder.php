<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Setup\PaymentChannelType;

class PaymentChannelTypeSeeder extends Seeder
{
    public function run(): void
    {
        $channels = [
            'SALARY',
            'CASH',
            'BANK_TRANSFER',
            'MANUAL',
            'USSD',
            'ONLINE_PAYMENT',
            'MOBILE_WALLET',
        ];

        foreach ($channels as $channel) {
            PaymentChannelType::firstOrCreate([
                'payment_channel_type_name' => $channel,
            ]);
        }
    }
}
