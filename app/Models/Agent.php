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
        'temperature', // De 0.0 a 1.0 - Sendo 0.0 respostas mais objetivas e precisas. Sendo 1.0 respostas mais humanas e criativas (imprevisíveis)
        'top_p', //Alternativa à temperatura. Mantém a “probabilidade cumulativa” (ex: 0.9 = considera as palavras mais prováveis até 90% de chance total).
        'frequency_penalty', //Reduz repetições (ex: útil se a IA está sendo redundante).
        'presence_penalty', //Incentiva a IA a explorar novos assuntos.
        'max_tokens', // limite
        'system_prompt', // Define a "personalidade" do agente
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
