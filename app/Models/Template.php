<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Template extends Model
{
    protected $table = 'templates';

    protected $fillable = [
        'store_id',
        'integration_id',
        'name',
        'language',
        'category',
        'body',
        'examples',
        'components',
        'payload',
        'status',
        'rejection_reason',
        'waba_template_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'examples' => 'array',
            'components' => 'array',
            'payload' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }
}
