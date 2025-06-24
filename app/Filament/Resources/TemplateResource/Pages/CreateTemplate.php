<?php

declare(strict_types = 1);

namespace App\Filament\Resources\TemplateResource\Pages;

use App\Filament\Resources\TemplateResource;
use App\Models\Integration;
use App\Services\Meta\WhatsAppService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateTemplate extends CreateRecord
{
    protected static string $resource = TemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $body = $data['body'] ?? '';
        $examples = collect($data['examples'] ?? []);

        preg_match_all('/{{\s*(\w+)\s*}}/', $body, $matches);
        $variablesInBody = collect($matches[1])->map(fn ($v) => Str::slug($v, '_'))->unique()->values();
        $variablesInRepeater = $examples->pluck('name')->map(fn ($v) => Str::slug($v, '_'))->unique()->values();

        $diff = $variablesInBody->diff($variablesInRepeater);

        if ($diff->isNotEmpty()) {
            throw ValidationException::withMessages([
                'body' => 'As seguintes variáveis estão na mensagem, mas não foram definidas nos exemplos: ' . $diff->implode(', '),
            ]);

            Notification::make()
                ->title('As seguintes variáveis estão na mensagem, mas não foram definidas nos exemplos: ' . $diff->implode(', '))
                ->danger()
                ->persistent()
                ->send();
        }

        $components = [
            [
                'type' => 'BODY',
                'text' => $body,
                'example' => [
                    'body_text' => [$examples->pluck('example')->toArray()],
                ],
            ],
        ];

        $templateData = [
            'name' => $data['name'],
            'language' => $data['language'],
            'category' => $data['category'],
            'components' => $components,
        ];

        $integration = Integration::findOrFail($data['integration_id']);
        $whatsappService = new WhatsAppService($integration);

        try {
            $response = $whatsappService->createTemplate($templateData);

            $data['waba_template_id'] = $response['id'] ?? null;
            $data['status'] = 'PENDING'; // Template enviado, aguardando aprovação
            $data['components'] = $components;
            $data['payload'] = $response;

            return $data;
        } catch (\Exception $e) {
            Log::error("Failed to create WhatsApp template: {$e->getMessage()}");
            Notification::make()
                ->title('Erro ao criar template')
                ->body('Não foi possível criar o template no WhatsApp. Verifique os logs.')
                ->danger()
                ->send();

            throw $e;
        }
    }
}
