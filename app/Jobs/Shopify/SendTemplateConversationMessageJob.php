<?php

declare(strict_types = 1);

namespace App\Jobs\Shopify;

use App\Enums\ConversationSenderTypeEnum;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Customer;
use App\Models\Integration;
use App\Models\Store;
use App\Services\Meta\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTemplateConversationMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Store $store,
        public Integration $integration,
        public Customer $customer,
        public Conversation $conversation
    ) {
    }

    public function handle(): void
    {
        try {
            $template = $this->integration->templates()->where('category', 'marketing')->first();

            if (!$template) {
                Log::warning("[SendTemplateConversationMessageJob] Nenhum template de marketing encontrado para a loja {$this->store->id}");

                return;
            }

            $service = new WhatsAppService($this->integration);

            $service->sendTemplate(
                $this->customer->whatsapp,
                $template->name,
                $template->language,
                $template->components
            );

            ConversationMessage::create([
                'store_id' => $this->store->id,
                'conversation_id' => $this->conversation->id,
                'sender_type' => ConversationSenderTypeEnum::AI,
                'content' => $template->body,
                'payload' => [
                    'template_name' => $template->name,
                    'language' => $template->language,
                    'components' => $template->components,
                ],
                'was_read' => false,
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("[SendTemplateConversationMessageJob] Falha: {$e->getMessage()}");
        }
    }
}
