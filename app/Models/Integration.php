<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Integration extends BaseModel
{
    protected $table = 'integrations';

    protected $fillable = [
        'store_id',
        'platform_id',
        'webhook',
        'secret',
        'is_active',
        'first_webhook_at',
        'last_webhook_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'first_webhook_at' => 'datetime',
            'last_webhook_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    public function aiContexts(): HasMany
    {
        return $this->hasMany(AiContext::class);
    }

    public function activate()
    {
        if (!$this->is_active) {
            $this->update([
                'is_active' => true,
                'first_webhook_at' => now(),
                'last_webhook_at' => now(),
            ]);
        } else {
            $this->update([
                'last_webhook_at' => now(),
            ]);
        }
    }
}
