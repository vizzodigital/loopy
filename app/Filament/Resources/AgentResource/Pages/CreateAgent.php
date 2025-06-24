<?php

declare(strict_types = 1);

namespace App\Filament\Resources\AgentResource\Pages;

use App\Filament\Resources\AgentResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateAgent extends CreateRecord
{
    protected static string $resource = AgentResource::class;

    protected function afterCreate(): void
    {
        $agent = $this->record;
        $data = $this->data;

        if (empty($data['stores'])) {
            $agent->stores()->attach(Filament::getTenant()->id, [
                'is_active' => $data['is_active_on_creation'] ?? true,
                'assigned_by' => auth()->guard('web')->id(),
                'assigned_at' => now(),
            ]);

            return;
        }

        foreach ($data['stores'] as $storeId) {
            $isActive = match ($data['activation_mode'] ?? 'current') {
                'all' => true,
                'current' => $storeId == Filament::getTenant()->id,
                'none' => false,
            };

            $agent->stores()->updateExistingPivot($storeId, [
                'is_active' => $isActive,
                'assigned_by' => auth()->guard('web')->id(),
                'assigned_at' => now(),
            ]);
        }
    }
}
