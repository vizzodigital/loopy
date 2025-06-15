<?php

declare(strict_types = 1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CartStatusEnum: string implements HasLabel
{
    case ABANDONED = 'abandoned'; // estado inicial
    case RECOVERED = 'recovered'; // estado recuperado
    case LOST = 'lost'; // estado perdido

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ABANDONED => 'Abandonado',
            self::RECOVERED => 'Recuperado',
            self::LOST => 'Perdido',
        };
    }
}
