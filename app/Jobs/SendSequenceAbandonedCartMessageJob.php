<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Enums\ConversationSenderTypeEnum;
use App\Enums\ConversationStatusEnum;
use App\Models\AbandonmentReason;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Integration;
use App\Services\Meta\WhatsAppService;
use App\Services\Zapi\ZapiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OpenAI;
use RuntimeException;

class SendSequenceAbandonedCartMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected Conversation $conversation,
        protected Integration $ia,
        protected Integration $whatsappIntegration,
        protected Agent $agent
    ) {
    }

    public function handle(): void
    {
        $customer = $this->conversation->abandonedCart->customer;

        $apiKey = $this->ia->configs['api_key'] ?? null;

        if (!$apiKey) {
            throw new RuntimeException('AI API key is not configured.');
        }

        $client = OpenAI::client($apiKey);

        $rules = <<<RULES
            Responda estritamente no seguinte formato JSON:
            {
                "close": (true ou false),
                "answer": "sua resposta ao cliente, deixe vazio se close for true",
                "reason": "um dos seguintes: price_concern, product_question, shipping_doubt, payment_issue, technical_difficulty, discount_request, trust_issue, cart_confusion, left_by_mistake ou other"
            }
            Analise cuidadosamente a mensagem recebida e retorne no formato acima sem nenhuma explicação adicional.
        RULES;

        $messages = [
            [
                'role' => 'system',
                'content' => $this->agent->system_prompt,
            ],
            [
                'role' => 'system',
                'content' => $this->conversation->system_prompt,
            ],
            [
                'role' => 'system',
                'content' => $rules,
            ],
        ];

        // Carrega histórico
        $histories = ConversationMessage::where('conversation_id', $this->conversation->id)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        foreach ($histories as $history) {
            $role = match ($history->sender_type) {
                ConversationSenderTypeEnum::CUSTOMER->value,
                ConversationSenderTypeEnum::HUMAN->value => 'user',
                ConversationSenderTypeEnum::AI->value => 'assistant',
            };

            $prefix = match ($history->sender_type) {
                ConversationSenderTypeEnum::CUSTOMER->value => 'Cliente:',
                ConversationSenderTypeEnum::HUMAN->value => 'Atendente:',
                ConversationSenderTypeEnum::AI->value => '',
            };

            $messages[] = [
                'role' => $role,
                'content' => trim($prefix . ' ' . $history->content),
            ];
        }

        // Consulta OpenAI
        $response = $client->chat()->create([
            'model' => $this->agent->model,
            'messages' => $messages,
            'temperature' => $this->agent->temperature,
            'top_p' => $this->agent->top_p ?? 1,
            'frequency_penalty' => $this->agent->frequency_penalty,
            'presence_penalty' => $this->agent->presence_penalty,
            'max_tokens' => $this->agent->max_tokens,
        ])->toArray();

        $content = $response['choices'][0]['message']['content'] ?? null;

        if (!$content) {
            throw new RuntimeException('AI response is empty.');
        }

        preg_match('/\{.*\}/s', $content, $matches);
        $jsonString = $matches[0] ?? null;

        if (!$jsonString) {
            throw new RuntimeException('No JSON found in AI response.');
        }

        try {
            $parsed = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException('Failed to parse AI response as JSON: ' . $e->getMessage());
        }

        $close = filter_var($parsed['close'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $answer = trim($parsed['answer'] ?? '');
        $reason = $parsed['reason'] ?? 'other';

        $validReasons = [
            'price_concern',
            'product_question',
            'shipping_doubt',
            'payment_issue',
            'technical_difficulty',
            'discount_request',
            'trust_issue',
            'cart_confusion',
            'left_by_mistake',
            'other',
        ];

        if (!in_array($reason, $validReasons)) {
            $reason = 'other';
        }

        // Atualiza motivo no carrinho
        $this->conversation->abandonedCart->update([
            'abandonment_reason_id' => AbandonmentReason::where('name', $reason)->value('id'),
        ]);

        // Fecha a conversa se necessário
        if ($close) {
            $this->conversation->update([
                'status' => ConversationStatusEnum::CLOSED,
            ]);

            return;
        }

        // Cria mensagem no histórico
        $conversationMessage = ConversationMessage::create([
            'store_id' => $this->conversation->store_id,
            'conversation_id' => $this->conversation->id,
            'sender_type' => ConversationSenderTypeEnum::AI,
            'content' => $answer,
            'payload' => $response,
            'sent_at' => now(),
        ]);

        // Envia via WhatsApp
        $whatsAppService = match ($this->whatsappIntegration->platform_id) {
            7 => new WhatsAppService($this->whatsappIntegration),
            8 => new ZapiService($this->whatsappIntegration),
            default => throw new RuntimeException('WhatsApp service not configured or unsupported platform.'),
        };

        if (empty($customer->whatsapp)) {
            throw new RuntimeException('Customer WhatsApp number is not set.');
        }

        $whatsAppService->sendText($customer->whatsapp, $conversationMessage->content);
    }
}
