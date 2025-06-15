<?php

declare(strict_types = 1);

namespace App\Enums;

enum ConversationSenderTypeEnum: string
{
    case CUSTOMER = 'customer';
    case AI = 'ai';
    case HUMAN = 'human';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
