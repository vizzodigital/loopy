<?php

declare(strict_types = 1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum IntegrationTypeEnum: string implements HasLabel
{
    case ECOMMERCE = 'ecommerce';
    case AI = 'ai';
    case WHATSAPP = 'whatsapp';
    case SOCIAL = 'social';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ECOMMERCE => 'Plataforma de E-commerce',
            self::AI => 'Agente de Inteligência Artificial',
            self::WHATSAPP => 'Integração com WhatsApp',
            self::SOCIAL => 'Rede Social',
        };
    }
}
