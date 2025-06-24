<?php

declare(strict_types = 1);

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Agent extends BaseModel
{
    protected $table = 'agents';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'model', //gpt-3.5-turbo, gpt-4o
        'temperature', // default 0.4 - Criatividade. Varia de 0 (mais preciso) a 2 (mais criativo) recomendado: 0.2 - 0.7
        'top_p', // Alternativa à temperature. Amostra os tokens do topo de prob. Use 1.0 na maioria dos casos
        'frequency_penalty', //default 0.3 - Penaliza repetição. 0 (padrão) até 2 - 0.2 - 0.8 se houver repetição
        'presence_penalty', //Estimula introduzir novos tópicos. -2 a 2 - Normalmente 0.0
        'max_tokens', //Limite de tokens na resposta - 100 - 2000 (dependendo do caso) default 1000
        'system_prompt',
        'is_test',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'top_p' => 'float',
            'frequency_penalty' => 'float',
            'presence_penalty' => 'float',
            'max_tokens' => 'integer',
            'is_test' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class)
                ->using(AgentStore::class)
                ->withPivot('is_active', 'assigned_by', 'assigned_at')
                ->withTimestamps();
    }

    public function getIsActiveForCurrentStoreAttribute(): bool
    {
        return $this->stores()->where('stores.id', Filament::getTenant()->id)->first()?->pivot?->is_active ?? false;
    }
}
