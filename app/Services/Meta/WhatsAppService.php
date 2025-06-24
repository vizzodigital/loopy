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

    public function __construct(
        protected Integration $integration
    ) {
        $this->accessToken = $integration->configs['access_token'] ?? throw new \Exception('Access token not configured.');
        $this->wabaId = $integration->configs['waba_id'] ?? throw new \Exception('WABA ID not configured.');
        $this->phoneNumberId = $integration->configs['phone_number_id'] ?? throw new \Exception('Phone number ID not configured.');
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
                ->{$method}($url, $method === 'get' ? [] : $params)
                ->throw()
                ->json();

            return $response;
        } catch (\Exception $e) {
            Log::error("WhatsApp API request failed: {$e->getMessage()}");

            throw new \Exception("Failed to communicate with WhatsApp API: {$e->getMessage()}");
        }
    }

    public function listTemplates(?string $after = null): array
    {
        $endpoint = "{$this->wabaId}/message_templates";
        $params = $after ? ['after' => $after] : [];

        return $this->makeRequest('get', $endpoint, $params);
    }

    public function createTemplate(array $templateData): array
    {
        $endpoint = "{$this->wabaId}/message_templates";

        return $this->makeRequest('post', $endpoint, $templateData);
    }

    public function deleteTemplate(string $templateName): array
    {
        $endpoint = "{$this->wabaId}/message_templates/{$templateName}";
        $response = $this->makeRequest('delete', $endpoint);

        return $response;
    }
}
