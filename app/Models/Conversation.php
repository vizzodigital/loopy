<?php

declare(strict_types = 1);

namespace App\Models;

use App\Enums\ConversationStatusEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends BaseModel
{
    protected $table = 'conversations';

    protected $fillable = [
        'store_id',
        'abandoned_cart_id',
        'status',
        'system_prompt',
        'started_at',
        'closed_at',
        'human_assumed_at',
        'human_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ConversationStatusEnum::class,
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
            'human_assumed_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function abandonedCart(): BelongsTo
    {
        return $this->belongsTo(AbandonedCart::class);
    }

    public function conversationMessages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }

    public function humanUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'human_user_id');
    }
}
