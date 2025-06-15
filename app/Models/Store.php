<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends BaseModel
{
    protected $table = 'stores';

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'plan_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class)
                ->using(AgentStore::class)
                ->withPivot('is_active', 'assigned_by', 'assigned_at')
                ->withTimestamps();
    }

    public function activeAgent(): ?Agent
    {
        return $this->agents()->wherePivot('is_active', true)->first();
    }
}
