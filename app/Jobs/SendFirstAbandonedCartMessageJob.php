<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Integration;
use App\Services\AiConversationService;
use App\Services\Meta\WhatsAppService;
use App\Services\Zapi\ZapiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendFirstAbandonedCartMessageJob implements ShouldQueue
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

        $conversationMessage = $aiService->sendMessage(
            $this->conversation,
            null // null ou a nova mensagem do usuário
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
