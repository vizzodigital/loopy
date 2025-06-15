<?php

declare(strict_types = 1);

namespace App\Models;

use App\Enums\IntegrationTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Integration extends BaseModel
{
    protected $table = 'integrations';

    protected $fillable = [
        'store_id',
        'platform_id',
        'webhook',
        'type',
        'configs',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => IntegrationTypeEnum::class,
            'configs' => 'array',
            'is_active' => 'boolean',
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

    public function isType(string $type): bool
    {
        return $this->type === $type;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function activate()
    {
        $configs = $this->configs ?? [];

        $now = now();

        $configs['last_webhook_at'] = $now;

        if (!$this->is_active) {
            $configs['first_webhook_at'] = $now;
            $this->update([
                'is_active' => true,
                'configs' => $configs,
            ]);
        } else {
            $this->update([
                'configs' => $configs,
            ]);
        }
    }
}
