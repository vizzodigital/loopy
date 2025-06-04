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
                'name' => 'Yampi', //1
            ],
            [
                'name' => 'Shopify', //2
            ],
            [
                'name' => 'WooCommerce', //3
            ],
            [
                'name' => 'PandaStore', //4
            ],
        ];

        foreach ($platforms as $platform) {
            \App\Models\Platform::create($platform);
        }
    }
}
