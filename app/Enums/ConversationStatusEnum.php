<?php

declare(strict_types = 1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ConversationStatusEnum: string implements HasLabel
{
    case OPEN = 'open'; //Conversa ativa, não resolvida
    case CLOSED = 'closed'; //Conversa fechada, resolvida (recuperada ou não)
    case PENDING = 'pending'; //Conversa pendente, customer não respondeu
    case HUMAN = 'human'; //Conversa assumida por humano (marca como resolvido manualmente) ou pendente após 24 horas - regra do whatsapp official para novas mensagens

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::OPEN => 'Aberta',
            self::CLOSED => 'Fechada',
            self::PENDING => 'Pendente',
            self::HUMAN => 'Assumida',
            default => null,
        };
    }
}
