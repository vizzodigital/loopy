<?php

declare(strict_types = 1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $platforms = [
            [
                'name' => 'Yampi',
            ],
            [
                'name' => 'Shopify',
            ],
            [
                'name' => 'WooCommerce',
            ],
            [
                'name' => 'PandaStore',
            ],
        ];

        foreach ($platforms as $platform) {
            \App\Models\Platform::create($platform);
        }
    }
}
