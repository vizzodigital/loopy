<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class AbandonmentReason extends BaseModel
{
    protected $table = 'abandonment_reasons';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function abandonedCarts(): HasMany
    {
        return $this->hasMany(AbandonedCart::class);
    }
}
