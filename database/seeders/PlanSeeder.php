<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::updateOrCreate(
            ['slug' => 'basic'],
            [
            'name' => 'Basic',
            'slug' => 'basic',
            'price' => 299.00,
            'description' => 'Plano básico',
            'features' => [
                'recovery' => true,
                'openai' => true,
                'deepseek' => false,
                'whatsapp' => true,
                'email' => false,
                'sms' => false,
                'telegram' => false,
                'wechat' => false,
                'instagram' => false,
                'messenger' => false,
            ],
            'is_active' => true,
        ]);
    }
}
