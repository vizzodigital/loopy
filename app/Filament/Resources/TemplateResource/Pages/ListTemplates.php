<?php

declare(strict_types = 1);

namespace App\Filament\Resources\TemplateResource\Pages;

use App\Enums\IntegrationTypeEnum;
use App\Filament\Resources\TemplateResource;
use App\Jobs\UpdateCreateTemplatesJob;
use App\Models\Integration;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTemplates extends ListRecords
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('syncTemplates')
                ->label('Sincronizar templates')
                ->requiresConfirmation()
                ->color('gray')
                ->icon('heroicon-s-arrow-path')
                ->action(function () {
                    $storeId = Filament::getTenant()->id;
                    $integration = Integration::where('store_id', $storeId)
                        ->where('platform_id', 7)
                        ->where('type', IntegrationTypeEnum::WHATSAPP)
                        ->first();

                    if (!$integration ||
                        $integration->platform_id !== 7 ||
                        !filled($integration->configs['phone_number_id'] ?? null) ||
                        !filled($integration->configs['waba_id'] ?? null) ||
                        !filled($integration->configs['access_token'] ?? null)) {
                        Notification::make()
                            ->title('Integração de WhatsApp não configurada')
                            ->body('Configure a integração de WhatsApp antes de sincronizar os templates.')
                            ->danger()
                            ->send();

                        return;
                    }

                    UpdateCreateTemplatesJob::dispatch($integration);

                    Notification::make()
                        ->title('Baixando templates existentes')
                        ->body('Em instantes, os templates serão sincronizados.')
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}
