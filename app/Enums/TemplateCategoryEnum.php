<?php

declare(strict_types = 1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TemplateCategoryEnum: string implements HasLabel, HasColor
{
    case MARKETING = 'marketing'; //Promoções, ofertas, lembretes de carrinho, anúncios, etc.
    case UTILITY = 'utility'; //Mensagens transacionais, confirmações, atualizações de pedidos.
    case AUTHENTICATION = 'authentication'; //Códigos de verificação, autenticação de dois fatores (2FA).

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::MARKETING => 'Marketing',
            self::UTILITY => 'Utilitários',
            self::AUTHENTICATION => 'Autenticação',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::MARKETING => 'primary',
            self::UTILITY => 'secondary',
            self::AUTHENTICATION => 'warning',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::MARKETING => 'Promoções, ofertas, lembretes de carrinho e campanhas.',
            self::UTILITY => 'Atualizações, confirmações, e notificações de pedidos.',
            self::AUTHENTICATION => 'Envio de códigos de verificação e autenticação de segurança.',
        };
    }
}
