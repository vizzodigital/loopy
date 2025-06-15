<?php

declare(strict_types = 1);

namespace App\Services\Meta;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    protected $apiUrl;

    protected $accessToken;

    protected $phoneNumberId;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url');
        $this->accessToken = config('services.whatsapp.access_token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
    }

    /**
     * Envia uma mensagem de texto simples
     *
     * @param string $recipientPhone Número do destinatário no formato internacional (ex.: +5511999999999)
     * @param string $message Texto da mensagem
     * @return array Resposta da API
     */
    public function sendText($recipientPhone, $message)
    {
        $endpoint = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->post($endpoint, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipientPhone,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message,
            ],
        ]);

        return $response->json();
    }

    /**
     * Envia uma mensagem baseada em um template
     *
     * @param string $recipientPhone Número do destinatário
     * @param string $templateName Nome do template aprovado
     * @param array $parameters Parâmetros para personalizar o template
     * @param string $language Código do idioma (ex.: pt_BR)
     * @return array Resposta da API
     */
    public function sendTemplateMessage($recipientPhone, $templateName, $parameters = [], $language = 'pt_BR')
    {
        $endpoint = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        $components = [];

        if (!empty($parameters)) {
            $components = [
                [
                    'type' => 'body',
                    'parameters' => array_map(function ($param) {
                        return ['type' => 'text', 'text' => $param];
                    }, $parameters),
                ],
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->post($endpoint, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipientPhone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
                'components' => $components,
            ],
        ]);

        return $response->json();
    }
}
