<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends BaseModel
{
    protected $table = 'customers';

    protected $fillable = [
        'store_id',
        'external_id',
        'name',
        'email',
        'phone',
        'whatsapp',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function abandonedCarts(): HasMany
    {
        return $this->hasMany(AbandonedCart::class);
    }
}
