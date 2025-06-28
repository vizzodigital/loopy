<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Enums\IntegrationTypeEnum;
use App\Models\Platform;
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
                'image' => 'http://recovery.app.test/images/platforms/yampi.png',
                'url' => 'https://yampi.com.br',
                'type' => IntegrationTypeEnum::ECOMMERCE,
                'is_active' => true,
                'is_beta' => true,
            ],
            [
                'name' => 'Shopify',
                'image' => 'http://recovery.app.test/images/platforms/shopify.png',
                'url' => 'https://shopify.com',
                'type' => IntegrationTypeEnum::ECOMMERCE,
                'is_active' => false,
                'is_beta' => true,
            ],
            [
                'name' => 'CartPanda',
                'image' => 'http://recovery.app.test/images/platforms/cartpanda.png',
                'url' => 'https://cartpanda.com.br',
                'type' => IntegrationTypeEnum::ECOMMERCE,
                'is_active' => false,
                'is_beta' => true,
            ],
            [
                'name' => 'WooCommerce',
                'image' => 'http://recovery.app.test/images/platforms/woocommerce.png',
                'url' => 'https://woocommerce.com',
                'type' => IntegrationTypeEnum::ECOMMERCE,
                'is_active' => false,
                'is_beta' => true,
            ],
            [
                'name' => 'OpenAI',
                'image' => 'http://recovery.app.test/images/platforms/openai.png',
                'url' => 'https://openai.com/api/',
                'type' => IntegrationTypeEnum::AI,
                'is_active' => true,
                'is_beta' => true,
            ],
            [
                'name' => 'DeepSeek',
                'image' => 'http://recovery.app.test/images/platforms/deepseek.png',
                'url' => 'https://platform.deepseek.com',
                'type' => IntegrationTypeEnum::AI,
                'is_active' => false,
                'is_beta' => true,
            ],
            [
                'name' => 'Whatsapp Business API - Official',
                'image' => 'http://recovery.app.test/images/platforms/whatsapp.png',
                'url' => 'https://business.whatsapp.com/products/business-platform',
                'type' => IntegrationTypeEnum::WHATSAPP,
                'is_active' => false,
                'is_beta' => true,
            ],
            [
                'name' => 'Z-API',
                'image' => 'http://recovery.app.test/images/platforms/zapi.png',
                'url' => 'https://zapi.io',
                'type' => IntegrationTypeEnum::WHATSAPP,
                'is_active' => true,
                'is_beta' => true,
            ],
            [
                'name' => 'VIZZAPP',
                'image' => 'http://recovery.app.test/images/platforms/vizzapp.png',
                'url' => 'https://waha.vizzo.digital',
                'type' => IntegrationTypeEnum::WHATSAPP,
                'is_active' => false,
                'is_beta' => true,
            ],
        ];

        foreach ($platforms as $platform) {
            Platform::updateOrCreate(
                ['name' => $platform['name']],
                $platform
            );
        }
    }
}
