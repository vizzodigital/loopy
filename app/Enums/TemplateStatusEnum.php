<?php

declare(strict_types = 1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TemplateStatusEnum: string implements HasLabel, HasColor
{
    case APPROVED = 'APPROVED';
    case PENDING = 'PENDING';
    case REJECTED = 'REJECTED';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::APPROVED => 'Aprovado',
            self::PENDING => 'Pendente',
            self::REJECTED => 'Rejeitado',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::APPROVED => 'success',
            self::PENDING => 'warning',
            self::REJECTED => 'danger',
        };
    }
}
