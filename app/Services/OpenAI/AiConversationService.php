<?php

declare(strict_types = 1);

namespace App\Services\OpenAI;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Integration;
use OpenAI;

class AiConversationService
{
    protected Integration $integration;

    protected Agent $agent;

    protected OpenAI\Client $client;

    public function __construct(Integration $integration, Agent $agent)
    {
        $this->integration = $integration;
        $this->agent = $agent;

        $apiKey = $integration->configs['api_key'] ?? null;

        if (!$apiKey) {
            throw new \RuntimeException('AI API key is not configured.');
        }

        $this->client = OpenAI::client($apiKey);
    }

    /**
     * Monta o histórico da conversa no formato OpenAI.
     */
    protected function buildMessages(Conversation $conversation, ?string $newUserMessage = null): array
    {
        $history = ConversationMessage::where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->get();

        $messages = [];

        if (!empty($this->agent->system_prompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->agent->system_prompt . ' ' . $conversation->system_prompt,
            ];
        }

        foreach ($history as $msg) {
            $prefix = match ($msg->sender_type) {
                'customer' => 'Cliente:',
                'human' => 'Atendente:',
                'ai' => '',
            };

            $messages[] = [
                'role' => $msg->sender_type === 'ai' ? 'assistant' : 'user',
                'content' => trim($prefix . ' ' . $msg->content),
            ];
        }

        if ($newUserMessage) {
            $messages[] = [
                'role' => 'user',
                'content' => 'Cliente: ' . $newUserMessage,
            ];
        }

        return $messages;
    }
}
