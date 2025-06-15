<?php

declare(strict_types = 1);

namespace App\Services\OpenAI;

use App\Enums\IntegrationTypeEnum;
use App\Models\Agent;
use App\Models\Integration;
use OpenAI;
use OpenAI\Exceptions\ErrorException;

class OpenAIService
{
    protected Integration $integration;

    protected Agent $agent;

    protected OpenAI\Client $client;

    /**
     * Inicializa o serviço com integração AI e agente ativo.
     *
     * @param Integration $integration
     * @param Agent $agent
     * @throws \InvalidArgumentException
     */
    public function __construct(Integration $integration, Agent $agent)
    {
        if ($integration->type !== IntegrationTypeEnum::AI) {
            throw new \InvalidArgumentException('Integration type must be AI');
        }

        if (!$agent->is_active) {
            throw new \InvalidArgumentException('Agent must be active');
        }

        $this->integration = $integration;
        $this->agent = $agent;

        $apiKey = $integration->configs['api_key'];

        if (empty($apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $this->client = OpenAI::client($apiKey);
    }

    /**
     * Envia uma conversa para o OpenAI e retorna a resposta.
     *
     * @param array $messages Array de mensagens no formato esperado pelo OpenAI (role + content)
     * @return array Resposta completa da API OpenAI
     * @throws ErrorException
     */
    public function chat(array $messages): array
    {
        if (!empty($this->agent->system_prompt)) {
            array_unshift($messages, [
                'role' => 'system',
                'content' => $this->agent->system_prompt,
            ]);
        }

        return $this->client->chat()->create([
            'model' => $this->agent->model,
            'messages' => $messages,
            'temperature' => $this->agent->temperature,
            'top_p' => $this->agent->top_p ?? 1,
            'frequency_penalty' => $this->agent->frequency_penalty,
            'presence_penalty' => $this->agent->presence_penalty,
            'max_tokens' => $this->agent->max_tokens,
        ])->toArray();
    }
}
