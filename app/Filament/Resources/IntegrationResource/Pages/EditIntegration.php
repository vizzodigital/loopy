<?php

declare(strict_types = 1);

namespace App\Filament\Resources\IntegrationResource\Pages;

use App\Filament\Resources\IntegrationResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditIntegration extends EditRecord
{
    protected static string $resource = IntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
            Action::make('verify')
                ->label('Verificar')
                ->requiresConfirmation()
                ->color('success')
                ->visible(
                    fn ($record) => $record->platform_id === 7 &&
                    filled($record->configs['phone_number_id'] ?? null) &&
                    filled($record->configs['waba_id'] ?? null) &&
                    filled($record->configs['access_token'] ?? null)
                )
                ->icon('heroicon-s-check-circle')
                ->action(function (array $data) {
                    // TODO chamada API
                }),

            $this->getSaveFormAction()
                ->formId('form'),
            $this->getCancelFormAction()
                ->formId('form'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', [$this->record]);
    }

    protected function afterSave(): void
    {
        $integration = $this->record;

        if ($this->record->platform_id >= 1 && $this->record->platform_id <= 4) {
            $integration->update([
                'configs' => [
                    'secret' => $this->record->configs['secret'] ?? null,
                ],
            ]);
        }

        if ($this->record->platform_id >= 5 && $this->record->platform_id <= 6) {
            $integration->update([
                'configs' => [
                    'api_key' => $this->record->configs['api_key'] ?? null,
                ],
            ]);
        }

        if ($this->record->platform_id == 7) {
            $integration->update([
                'configs' => [
                    'phone_number_id' => $this->record->configs['phone_number_id'] ?? null,
                    'waba_id' => $this->record->configs['waba_id'] ?? null,
                    'access_token' => $this->record->configs['access_token'] ?? null,
                ],
            ]);
        }

        if ($this->record->platform_id == 8) {
            $integration->update([
                'configs' => [
                    'instance' => $this->record->configs['instance'] ?? null,
                    'api_token' => $this->record->configs['api_token'] ?? null,
                    'security_token' => $this->record->configs['security_token'] ?? null,
                ],
            ]);
        }

        if ($this->record->platform_id == 9) {
            $integration->update([
                'configs' => [
                    'session' => $this->record->configs['session'] ?? null,
                    'api_key' => $this->record->configs['api_key'] ?? null,
                ],
            ]);
        }
    }
}
