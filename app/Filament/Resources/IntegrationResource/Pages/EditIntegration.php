<?php

declare(strict_types = 1);

namespace App\Filament\Resources\IntegrationResource\Pages;

use App\Filament\Resources\IntegrationResource;
use App\Models\Integration;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Http;

class EditIntegration extends EditRecord
{
    protected static string $resource = IntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
            Action::make('authenticate')
                ->label('Conectar')
                ->requiresConfirmation()
                ->color('success')
                ->visible(fn ($record) => $record->platform_id === 2)
                ->icon('heroicon-s-check-circle')
                ->form([
                    TextInput::make('nameStore')
                        ->label('Loja')
                        ->suffix('.myshopify.com'),
                ])
                ->action(function (array $data, Integration $record) {
                    $storePrefix = strtolower(trim($data['nameStore']));
                    $shop = $storePrefix . '.myshopify.com';

                    $response = Http::head("https://{$shop}");

                    if ($response->failed()) {
                        Notification::make()
                            ->title('Loja Shopify inválida')
                            ->danger()
                            ->body("O domínio {$shop} não pôde ser verificado.")
                            ->send();

                        return;
                    }

                    $record->update([
                        'configs' => array_merge($record->configs ?? [], ['shop' => $shop]),
                    ]);

                    $installUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
                        'client_id' => config('services.shopify.client_id'),
                        'scope' => 'read_orders,read_customers',
                        'redirect_uri' => route('shopify.oauth.callback'),
                        'state' => $record->webhook,
                        'grant_options[]' => 'per-user',
                    ]);

                    return redirect($installUrl);
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
