<?php

declare(strict_types = 1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PlatformsEnum: int implements HasLabel
{
    case YAMPI = 1;
    case SHOPIFY = 2;
    case CARTPANDA = 3;
    case WOOCOMMERCE = 4;

    case OPENAI = 5;
    case DEEPSEEK = 6;

    case WHATSAPP = 7;
    case ZAPI = 8;
    case WAHA = 9;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::YAMPI => 'Yampi',
            self::SHOPIFY => 'Shopify',
            self::CARTPANDA => 'CartPanda',
            self::WOOCOMMERCE => 'WooCommerce',
            self::OPENAI => 'OpenAI',
            self::DEEPSEEK => 'DeepSeek',
            self::WHATSAPP => 'WhatsApp',
            self::ZAPI => 'Zapi',
            self::WAHA => 'Waha',
        };
    }
}
