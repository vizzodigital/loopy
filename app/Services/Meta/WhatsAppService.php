<?php

declare(strict_types = 1);

namespace App\Services\Meta;

use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl = 'https://graph.facebook.com/v23.0';

    protected string $accessToken;

    protected string $wabaId;

    protected string $phoneNumberId;

    public function __construct(protected Integration $integration)
    {
        $this->accessToken = $integration->configs['access_token']
            ?? throw new \Exception('Access token not configured.');
        $this->wabaId = $integration->configs['waba_id']
            ?? throw new \Exception('WABA ID not configured.');
        $this->phoneNumberId = $integration->configs['phone_number_id']
            ?? throw new \Exception('Phone number ID not configured.');
    }

    protected function getHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ];
    }

    protected function makeRequest(string $method, string $endpoint, array $params = []): array
    {
        $url = "{$this->baseUrl}/{$endpoint}";

        if ($method === 'get' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->{$method}($url, $method === 'get' ? [] : $params);

            Log::info('WhatsApp API Request', [
                'method' => $method,
                'url' => $url,
                'params' => $method !== 'get' ? $params : [],
                'status' => $response->status(),
            ]);

            if (!$response->successful()) {
                $error = $response->json('error') ?? $response->body();

                throw new \Exception("WhatsApp API Error: " . json_encode($error));
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("WhatsApp API request failed", [
                'method' => $method,
                'endpoint' => $endpoint,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("Failed to communicate with WhatsApp API: {$e->getMessage()}");
        }
    }

    public function createTemplate(array $templateData): array
    {
        $this->validateTemplateData($templateData);

        $endpoint = "{$this->wabaId}/message_templates";

        Log::info('Creating WhatsApp template', [
            'template_name' => $templateData['name'],
            'template_data' => $templateData,
        ]);

        $response = $this->makeRequest('post', $endpoint, $templateData);

        Log::info('WhatsApp template created successfully', [
            'template_name' => $templateData['name'],
            'template_id' => $response['id'] ?? null,
            'status' => $response['status'] ?? null,
        ]);

        return $response;
    }

    public function getTemplate(string $templateId): array
    {
        $endpoint = "{$templateId}";

        return $this->makeRequest('get', $endpoint);
    }

    public function updateTemplateStatus(string $templateId): array
    {
        return $this->getTemplate($templateId);
    }

    public function listTemplates(?string $after = null, array $filters = []): array
    {
        $endpoint = "{$this->wabaId}/message_templates";
        $params = array_filter([
            'after' => $after,
            'limit' => $filters['limit'] ?? 100,
            'fields' => $filters['fields'] ?? 'name,status,category,language',
        ]);

        return $this->makeRequest('get', $endpoint, $params);
    }

    public function deleteTemplate(string $templateName): array
    {
        $endpoint = "{$this->wabaId}/message_templates";
        $params = ['name' => $templateName];

        Log::info('Deleting WhatsApp template', ['template_name' => $templateName]);

        return $this->makeRequest('delete', $endpoint, $params);
    }

    public function sendTemplate(string $to, string $templateName, string $language = 'pt_BR', array $components = []): array
    {
        $endpoint = "{$this->phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $language,
                ],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        Log::info('Sending WhatsApp template message', [
            'to' => $to,
            'template' => $templateName,
            'language' => $language,
        ]);

        return $this->makeRequest('post', $endpoint, $payload);
    }

    /**
     * Enviar mensagem de texto simples
     */
    public function sendText(string $to, string $message): array
    {
        $endpoint = "{$this->phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $message,
            ],
        ];

        return $this->makeRequest('post', $endpoint, $payload);
    }

    /**
     * Validar dados do template antes de criar
     */
    private function validateTemplateData(array $templateData): void
    {
        $required = ['name', 'language', 'category', 'components'];

        foreach ($required as $field) {
            if (!isset($templateData[$field])) {
                throw new \Exception("Campo obrigatório ausente: {$field}");
            }
        }

        // Validar categoria
        $validCategories = ['MARKETING', 'UTILITY', 'AUTHENTICATION'];

        if (!in_array($templateData['category'], $validCategories)) {
            throw new \Exception("Categoria inválida. Use: " . implode(', ', $validCategories));
        }

        // Validar componentes
        if (empty($templateData['components'])) {
            throw new \Exception("Template deve ter pelo menos um componente");
        }

        foreach ($templateData['components'] as $component) {
            if (!isset($component['type']) || !isset($component['text'])) {
                throw new \Exception("Componente inválido: type e text são obrigatórios");
            }
        }
    }

    /**
     * Obter status de todos os templates
     */
    public function getTemplatesStatus(): array
    {
        $templates = $this->listTemplates();

        return collect($templates['data'] ?? [])
            ->map(function ($template) {
                return [
                    'id' => $template['id'],
                    'name' => $template['name'],
                    'status' => $template['status'],
                    'category' => $template['category'],
                    'language' => $template['language'],
                ];
            })
            ->toArray();
    }
}
