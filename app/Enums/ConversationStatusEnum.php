<?php

declare(strict_types = 1);

namespace App\Enums;

enum ConversationStatusEnum: string
{
    case OPEN = 'open'; //Conversa ativa, não resolvida
    case CLOSED = 'closed'; //Conversa fechada, resolvida (recuperada ou não)
    case PENDING = 'pending'; //Conversa pendente, customer não respondeu
    case HUMAN = 'human'; //Conversa assumida por humano (marca como resolvido manualmente) ou pendente após 24 horas - regra do whatsapp official para novas mensagens

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
