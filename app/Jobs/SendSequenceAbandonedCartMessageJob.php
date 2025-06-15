<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Integration;
use App\Services\AiConversationService;
use App\Services\Meta\WhatsAppService;
use App\Services\Zapi\ZapiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSequenceAbandonedCartMessageJob implements ShouldQueue
{
    use Queueable;

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

        $aiService = new AiConversationService($this->ia, $this->agent);

        $lastMessage = ConversationMessage::where('conversation_id', $this->conversation->id)
            ->where('sender_type', 'customer')
            ->orderBy('created_at', 'desc')
            ->first();

        $conversationMessage = $aiService->sendMessage(
            $this->conversation,
            $lastMessage->content
        );

        $whatsAppService = match ($this->whatsappIntegration->platform_id) {
            7 => new WhatsAppService($this->whatsappIntegration),
            8 => new ZapiService($this->whatsappIntegration),
            default => null,
        };

        if (!$whatsAppService) {
            throw new \RuntimeException('WhatsApp service not configured or unsupported platform.');
        }

        if (empty($customer->whatsapp)) {
            throw new \RuntimeException('Customer WhatsApp number is not set.');
        }

        $whatsAppService->sendText($customer->whatsapp, $conversationMessage->content);
    }
}
