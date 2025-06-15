<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AgentStore extends Pivot
{
    protected $table = 'agent_store';

    protected $fillable = [
        'agent_id',
        'store_id',
        'is_active',
        'assigned_by',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'assigned_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        static::saving(function (AgentStore $pivot) {
            if ($pivot->is_active) {
                // Desativa todos os outros agentes dessa store
                self::where('store_id', $pivot->store_id)
                    ->where('id', '!=', $pivot->id)
                    ->update(['is_active' => false]);
            }
        });
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
