<?php

declare(strict_types = 1);

namespace App\Models;

use App\Enums\IntegrationTypeEnum;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Platform extends BaseModel
{
    protected $table = 'platforms';

    protected $fillable = [
        'name',
        'image',
        'url',
        'type',
        'is_active',
        'is_beta',
    ];

    protected function casts(): array
    {
        return [
            'type' => IntegrationTypeEnum::class,
            'is_active' => 'boolean',
            'is_beta' => 'boolean',
        ];
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }
}
