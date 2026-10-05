<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\JerseySize;
use App\Models\SystemSetting;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin User
        User::updateOrCreate(
            ['email' => 'admin@regrun.test'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
            ]
        );

        // 2. Master Jersey Sizes (XS to 5XL)
        $sizes = [
            ['code' => 'XS', 'label' => 'Ukuran XS', 'order' => 1],
            ['code' => 'S', 'label' => 'Ukuran S', 'order' => 2],
            ['code' => 'M', 'label' => 'Ukuran M', 'order' => 3],
            ['code' => 'L', 'label' => 'Ukuran L', 'order' => 4],
            ['code' => 'XL', 'label' => 'Ukuran XL', 'order' => 5],
            ['code' => 'XXL', 'label' => 'Ukuran XXL', 'order' => 6],
            ['code' => '3XL', 'label' => 'Ukuran 3XL', 'order' => 7],
            ['code' => '4XL', 'label' => 'Ukuran 4XL', 'order' => 8],
            ['code' => '5XL', 'label' => 'Ukuran 5XL', 'order' => 9],
        ];

        foreach ($sizes as $s) {
            JerseySize::updateOrCreate(
                ['size_code' => $s['code']],
                [
                    'label' => $s['label'],
                    'gender_cut' => 'unisex',
                    'is_available' => true,
                    'sort_order' => $s['order'],
                ]
            );
        }

        // 3. Realistic Dummy Event
        $event = Event::updateOrCreate(
            ['slug' => 'nusantara-run-2026'],
            [
                'title' => 'Nusantara Sunset Run 2026',
                'description' => 'Event lari prestisius sore hari melintasi ikon ibukota dengan pemandangan matahari terbenam. Dilengkapi jersey eksklusif, medali finisher logam tebal, BIB timing chip, dan refreshment melimpah.',
                'venue_name' => 'Plaza Barat Gelora Bung Karno',
                'venue_address' => 'Jl. Pintu Satu Senayan, Gelora, Tanah Abang, Jakarta Pusat',
                'race_date' => '2026-11-15',
                'race_start_time' => '16:00:00',
                'rpc_start_date' => '2026-11-12',
                'rpc_end_date' => '2026-11-14',
                'rpc_location' => 'Exhibition Hall A, GBK Senayan',
                'is_active' => true,
                'is_default' => true,
            ]
        );

        // 4. Ticket Categories
        $categories = [
            [
                'name' => '5K Fun Run',
                'code' => '5K',
                'description' => 'Kategori santai keluarga & pemula. Termasuk: Jersey Dryfit, Medali Finisher, BIB, Refreshment & Asuransi.',
                'price' => 175000,
                'early_bird_price' => 145000,
                'early_bird_end_date' => now()->addDays(20),
                'quota' => 500,
                'min_age' => 10,
                'sort_order' => 1,
            ],
            [
                'name' => '10K Open Category',
                'code' => '10K',
                'description' => 'Tantangan jarak menengah untuk pelari reguler. Termasuk: Jersey Dryfit, Medali Finisher, Timing Chip BIB, Refreshment & Asuransi.',
                'price' => 275000,
                'early_bird_price' => 225000,
                'early_bird_end_date' => now()->addDays(20),
                'quota' => 350,
                'min_age' => 14,
                'sort_order' => 2,
            ],
            [
                'name' => '21K Half Marathon',
                'code' => '21K',
                'description' => 'Kategori kompetisi lari jarak jauh 21.0975 km. Termasuk: Jersey Dryfit, Finisher Tee Khusus, Medali Emas Finisher, BIB Timing, Asuransi Medis.',
                'price' => 385000,
                'early_bird_price' => 320000,
                'early_bird_end_date' => now()->addDays(20),
                'quota' => 200,
                'min_age' => 17,
                'sort_order' => 3,
            ],
        ];

        foreach ($categories as $cat) {
            TicketCategory::updateOrCreate(
                ['event_id' => $event->id, 'code' => $cat['code']],
                $cat
            );
        }

        // 5. System Settings
        $settings = [
            ['key' => 'tripay_merchant_code', 'value' => 'T39430', 'group' => 'tripay'],
            ['key' => 'tripay_api_key', 'value' => 'DEV-ef2bKOHNkSJqCVWJ85wTIKYOYXm4m40Q7Gfioc5N', 'group' => 'tripay'],
            ['key' => 'tripay_private_key', 'value' => 'f85W1-PuaJ2-J3i96-XoBzw-WBtLf', 'group' => 'tripay'],
            ['key' => 'tripay_sandbox', 'value' => '1', 'group' => 'tripay'],
            ['key' => 'mailketing_api_token', 'value' => '308b31d3313311776744479fa8fd7eb3', 'group' => 'mailketing'],
            ['key' => 'mailketing_sender_email', 'value' => 'hi@jelatik.com', 'group' => 'mailketing'],
            ['key' => 'mailketing_sender_name', 'value' => 'Panitia Nusantara Sunset Run', 'group' => 'mailketing'],
        ];

        foreach ($settings as $set) {
            SystemSetting::set($set['key'], $set['value'], $set['group']);
        }
    }
}
