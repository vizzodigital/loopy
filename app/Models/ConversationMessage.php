<?php

declare(strict_types = 1);

namespace App\Models;

use App\Enums\ConversationSenderTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends BaseModel
{
    protected $table = 'conversation_messages';

    protected $fillable = [
        'store_id',
        'conversation_id',
        'sender_type',
        'content',
        'payload',
        'was_read',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sender_type' => ConversationSenderTypeEnum::class,
            'payload' => 'array',
            'was_read' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
