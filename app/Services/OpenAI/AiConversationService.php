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
        if ($integration->type !== 'ai') {
            throw new \InvalidArgumentException('Integration type must be AI');
        }

        if (!$agent->is_active) {
            throw new \InvalidArgumentException('Agent must be active');
        }

        $this->integration = $integration;
        $this->agent = $agent;

        $apiKey = $integration->configs['api_key'] ?? null;

        if (!$apiKey) {
            throw new \RuntimeException('AI API key is not configured.');
        }

        $this->client = OpenAI::client($apiKey);
    }

    /**
     * Envia uma mensagem para IA com o contexto da conversa.
     */
    public function sendMessage(Conversation $conversation, ?string $userMessage = null): ConversationMessage
    {
        $messages = $this->buildMessages($conversation, $userMessage);

        $response = $this->client->chat()->create([
            'model' => $this->agent->model,
            'messages' => $messages,
            'temperature' => $this->agent->temperature,
            'top_p' => $this->agent->top_p ?? 1,
            'frequency_penalty' => $this->agent->frequency_penalty,
            'presence_penalty' => $this->agent->presence_penalty,
            'max_tokens' => $this->agent->max_tokens,
        ])->toArray();

        $content = $response['choices'][0]['message']['content'] ?? null;

        return ConversationMessage::create([
            'store_id' => $conversation->store_id,
            'conversation_id' => $conversation->id,
            'sender_type' => 'ai',
            'content' => $content,
            'payload' => $response,
            'data' => null,
        ]);
    }

    /**
     * Monta o histórico da conversa no formato OpenAI.
     */
    protected function buildMessages(Conversation $conversation, ?string $newUserMessage = null): array
    {
        $history = ConversationMessage::where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->take(10)
            ->get();

        $messages = [];

        if (!empty($this->agent->system_prompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->agent->system_prompt,
            ];
        }

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg->sender_type === 'ai' ? 'assistant' : 'user',
                'content' => $msg->content,
            ];
        }

        if ($newUserMessage) {
            $messages[] = [
                'role' => 'user',
                'content' => $newUserMessage,
            ];
        }

        return $messages;
    }
}
