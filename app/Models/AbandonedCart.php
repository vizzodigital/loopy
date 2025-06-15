<?php

declare(strict_types = 1);

namespace App\Models;

use App\Enums\CartStatusEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AbandonedCart extends BaseModel
{
    protected $table = 'abandoned_carts';

    protected $fillable = [
        'store_id',
        'customer_id',
        'abandonment_reason_id',
        'external_cart_id',
        'cart_data',
        'customer_data',
        'total_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cart_data' => 'array',
            'customer_data' => 'array',
            'total_amount' => 'decimal:2',
            'status' => CartStatusEnum::class,
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function abandonmentReason(): BelongsTo
    {
        return $this->belongsTo(AbandonmentReason::class);
    }

    public function conversations(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function isRecovered(): bool
    {
        return $this->status === CartStatusEnum::RECOVERED;
    }

    public function isAbandoned(): bool
    {
        return $this->status === CartStatusEnum::ABANDONED;
    }

    public function isLost(): bool
    {
        return $this->status === CartStatusEnum::LOST;
    }
}
