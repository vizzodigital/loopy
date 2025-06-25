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

        preg_match_all('/{{\s*(\w+)\s*}}/', $body, $matches);
        $variables = collect($matches[1])
            ->map(fn ($v) => Str::slug($v, '_')) // slug garante uniformidade
            ->unique()
            ->values();

        // Validação: por enquanto, apenas {{name}}
        $allowed = collect(['name']);
        $diff = $variables->diff($allowed);

        if ($diff->isNotEmpty()) {
            throw ValidationException::withMessages([
                'body' => 'Você só pode usar a variável {{name}} na mensagem.',
            ]);
        }

        $components = [
            [
                'type' => 'BODY',
                'text' => $body,
            ],
        ];

        // Se usar {{name}}, adiciona o example obrigatório para o WhatsApp
        if ($variables->contains('name')) {
            $components[0]['example'] = [
                'body_text' => [['João']],
            ];
        }

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
            $data['status'] = 'PENDING';
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
