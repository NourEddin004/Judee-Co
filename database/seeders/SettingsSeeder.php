<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'logo' => SeedData::image('logo.png', 'brand/logo.png'),
            'phone_display' => '+962 79 044 2964',
            'phone_international' => '962790442964',
            'whatsapp_international' => '962790442964',
            'instagram_url' => 'https://instagram.com/judee.co',
            'instagram_handle' => '@judee.co',
            'usd_rate' => '1.41',
            'order_email' => '',
            'store_paused' => '0',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
