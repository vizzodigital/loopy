<?php

declare(strict_types = 1);

namespace App\Filament\Resources\TemplateResource\Pages;

use App\Filament\Resources\TemplateResource;
use App\Models\Integration;
use App\Services\Meta\WhatsAppService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateTemplate extends CreateRecord
{
    protected static string $resource = TemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validateTemplateName($data['name']);

        $body = $data['body'] ?? '';
        $processedData = $this->processTemplateBody($body);

        $templateData = [
            'name' => strtolower(Str::slug($data['name'], '_')), // WhatsApp exige lowercase
            'language' => $data['language'],
            'category' => $data['category'],
            'components' => $processedData['components'],
        ];

        $integration = Integration::findOrFail($data['integration_id']);

        try {
            $whatsappService = new WhatsAppService($integration);
            $response = $whatsappService->createTemplate($templateData);

            $data['name'] = $templateData['name']; // usar nome processado
            $data['waba_template_id'] = $response['id'] ?? null;
            $data['status'] = $response['status'] ?? 'PENDING';
            $data['components'] = $processedData['components'];
            $data['variables'] = $processedData['variables'];
            $data['payload'] = $response;

            return $data;
        } catch (\Exception $e) {
            Log::error("Failed to create WhatsApp template", [
                'template_name' => $templateData['name'],
                'error' => $e->getMessage(),
                'template_data' => $templateData,
            ]);

            $errorMessage = $this->parseWhatsAppError($e->getMessage());

            Notification::make()
                ->title('Erro ao criar template')
                ->body($errorMessage)
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'name' => $errorMessage,
            ]);
        }
    }

    /**
     * Validar nome do template conforme regras do WhatsApp
     */
    private function validateTemplateName(string $name): void
    {
        // WhatsApp rules: apenas letras, números e underscore, máximo 512 caracteres
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw ValidationException::withMessages([
                'name' => 'Nome do template deve conter apenas letras, números e underscore.',
            ]);
        }

        if (strlen($name) > 512) {
            throw ValidationException::withMessages([
                'name' => 'Nome do template deve ter no máximo 512 caracteres.',
            ]);
        }
    }

    /**
     * Processar body do template e extrair variáveis
     */
    private function processTemplateBody(string $body): array
    {
        preg_match_all('/{{\s*(\w+)\s*}}/', $body, $matches);
        $variables = collect($matches[1])
            ->map(fn ($v) => Str::slug($v, '_'))
            ->unique()
            ->values();

        // Validação de variáveis permitidas
        $allowed = collect(['name']); // expandir com o tempo
        $diff = $variables->diff($allowed);

        if ($diff->isNotEmpty()) {
            throw ValidationException::withMessages([
                'body' => "Variáveis não permitidas: " . $diff->implode(', ') .
                         ". Variáveis permitidas: " . $allowed->implode(', '),
            ]);
        }

        // Montar componentes
        $components = [
            [
                'type' => 'BODY',
                'text' => $body,
            ],
        ];

        // Adicionar examples para variáveis (obrigatório no WhatsApp)
        if ($variables->isNotEmpty()) {
            $examples = $this->generateExamples($variables);
            $components[0]['example'] = [
                'body_text' => [$examples],
            ];
        }

        return [
            'components' => $components,
            'variables' => $variables->toArray(),
        ];
    }

    /**
     * Gerar examples para as variáveis
     */
    private function generateExamples(Collection $variables): array
    {
        $exampleMap = [
            'name' => 'João Silva',
        ];

        return $variables->map(fn ($var) => $exampleMap[$var] ?? 'Exemplo')->toArray();
    }

    /**
     * Interpretar erros do WhatsApp para mensagens mais amigáveis
     */
    private function parseWhatsAppError(string $error): string
    {
        $errorMappings = [
            'template name already exists' => 'Já existe um template com este nome.',
            'invalid template name' => 'Nome do template inválido.',
            'template creation limit reached' => 'Limite de templates atingido.',
            'invalid parameter' => 'Parâmetros inválidos no template.',
        ];

        foreach ($errorMappings as $pattern => $message) {
            if (str_contains(strtolower($error), $pattern)) {
                return $message;
            }
        }

        return 'Erro desconhecido ao criar template. Verifique os logs para mais detalhes.';
    }
}
