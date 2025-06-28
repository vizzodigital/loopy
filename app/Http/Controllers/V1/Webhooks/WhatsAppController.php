<?php

declare(strict_types = 1);

namespace App\Http\Controllers\V1\Webhooks;

use App\Enums\ConversationSenderTypeEnum;
use App\Enums\ConversationStatusEnum;
use App\Enums\IntegrationTypeEnum;
use App\Http\Controllers\Controller;
use App\Jobs\SendSequenceAbandonedCartMessageJob;
use App\Jobs\TranscribeAudioMessageJob;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Customer;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppController extends Controller
{
    public function __invoke(Request $request, string $webhook)
    {
        Log::info('Webhook received', [
            'webhook' => $webhook,
            'data' => $request->all(),
        ]);

        if (!Str::isUuid($webhook)) {
            return response()->json([
                'error' => 'Invalid webhook format',
            ], Response::HTTP_BAD_REQUEST);
        }

        $integration = Integration::where('webhook', $webhook)
            ->with(['store', 'platform'])
            ->first();

        if (!$integration) {
            return response()->json([
                'error' => 'Webhook not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$integration->store->is_active) {
            return response()->json([
                'error' => 'Store is not active',
            ], Response::HTTP_FORBIDDEN);
        }

        $data = $request->json()->all();

        try {
            $this->processWebhookData($integration, $data);

            return response()->json(['status' => 'success'], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'webhook' => $webhook,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function processWebhookData(Integration $integration, array $payload)
    {
        switch ($payload['field']) {
            case 'message_template_components_update':
                return $this->messageTemplateComponentsUpdate($integration, $payload);

                break;
            case 'message_template_quality_update':
                return $this->messageTemplateQualityUpdate($integration, $payload);

                break;
            case 'message_template_status_update':
                return $this->messageTemplateStatusUpdate($integration, $payload);

                break;
            case 'messages':
                return $this->messages($integration, $payload);

                break;
            case 'template_category_update':
                return $this->templateCategoryUpdate($integration, $payload);

                break;
            default:
                break;
        }
    }

    private function messageTemplateComponentsUpdate(Integration $integration, array $payload)
    {
        //{
        //     "field": "message_template_components_update",
        //     "value": {
        //         "message_template_id": 12345678,
        //         "message_template_name": "my_message_template",
        //         "message_template_language": "en-US",
        //         "message_template_title": "message header",
        //         "message_template_element": "message body",
        //         "message_template_footer": "message footer",
        //         "message_template_buttons": [
        //         {
        //             "message_template_button_type": "URL",
        //             "message_template_button_text": "button text",
        //             "message_template_button_url": "https://example.com",
        //             "message_template_button_phone_number": "12342342345"
        //         }
        //         ]
        //     }
        // }
    }

    private function messageTemplateQualityUpdate(Integration $integration, array $payload)
    {
        //{
        //     "field": "message_template_quality_update",
        //     "value": {
        //         "previous_quality_score": "GREEN",
        //         "new_quality_score": "YELLOW",
        //         "message_template_id": 12345678,
        //         "message_template_name": "my_message_template",
        //         "message_template_language": "pt-BR"
        //     }
        // }
    }

    private function messageTemplateStatusUpdate(Integration $integration, array $payload)
    {
        $template = $integration->templates()->where('waba_template_id', $payload['value']['message_template_id'])->first();

        if ($template) {
            $template->status = $payload['value']['event'];
            $template->save();
        }
        // {
        //     "field": "message_template_status_update",
        //     "value": {
        //         "event": "APPROVED",
        //         "message_template_id": 12345678,
        //         "message_template_name": "my_message_template",
        //         "message_template_language": "pt-BR",
        //         "reason": null
        //     }
        // }
    }

    private function messages(Integration $integration, array $payload)
    {
        if (!isset($payload['value']['messages'][0]) || !isset($payload['value']['metadata'])) {
            throw new \Exception('Invalid payload structure');
        }

        $message = $payload['value']['messages'][0];
        $metadata = $payload['value']['metadata'];

        $fromMe = ($metadata['display_phone_number'] === $message['from']);
        $whatsapp = $message['from'];

        $customer = Customer::with('abandonedCarts')
            ->where('store_id', $integration->store_id)
            ->where('whatsapp', $whatsapp)
            ->first();

        if (!$customer) {
            throw new \Exception('Customer not found');
        }

        $abandonedCart = $customer->abandonedCarts->first();

        if (!$abandonedCart) {
            throw new \Exception('Abandoned cart not found');
        }

        $conversation = Conversation::where('abandoned_cart_id', $abandonedCart->id)
            ->where('store_id', $integration->store_id)
            ->first();

        match ($message['type']) {
            'text' => $this->resolveText($conversation, $payload, $fromMe),
            'audio' => $this->resolveAudio($conversation, $payload, $fromMe),
            default => null,
        };
    }

    private function templateCategoryUpdate(Integration $integration, array $payload)
    {
        $template = $integration->templates()->where('waba_template_id', $payload['value']['message_template_id'])->first();

        if ($template) {
            $template->category = $payload['value']['new_category'];
            $template->save();
        }

        // {
        //     "field": "template_category_update",
        //     "value": {
        //         "message_template_id": 12345678,
        //         "message_template_name": "my_message_template",
        //         "message_template_language": "en-US",
        //         "previous_category": "MARKETING",
        //         "new_category": "UTILITY",
        //         "correct_category": "MARKETING"
        //     }
        // }
    }

    public function resolveText(Conversation $conversation, array $payload, bool $fromMe)
    {
        $message = trim($payload['value']['messages'][0]['text']['body'] ?? '');

        if (empty($message)) {
            return;
        }

        if (
            in_array($conversation->status, [
                ConversationStatusEnum::HUMAN,
                ConversationStatusEnum::CLOSED,
            ])
        ) {
            return;
        }

        if ($fromMe) {
            if ($this->isMessageFromAi($conversation, $message)) {
                return;
            }

            $conversation->update(['status' => ConversationStatusEnum::HUMAN]);

            ConversationMessage::create([
                'store_id' => $conversation->store_id,
                'conversation_id' => $conversation->id,
                'sender_type' => ConversationSenderTypeEnum::HUMAN,
                'content' => $message,
                'payload' => $payload,
            ]);

            return;
        }

        ConversationMessage::create([
            'store_id' => $conversation->store_id,
            'conversation_id' => $conversation->id,
            'sender_type' => ConversationSenderTypeEnum::CUSTOMER,
            'content' => $message,
            'payload' => $payload,
        ]);

        $ia = $conversation->store->integrations()
            ->where('type', IntegrationTypeEnum::AI)
            ->where('is_active', true)
            ->first();

        $agent = $conversation->store->activeAgent();

        $whatsapp = $conversation->store->integrations()
            ->where('type', IntegrationTypeEnum::WHATSAPP)
            ->first();

        SendSequenceAbandonedCartMessageJob::dispatch(
            $conversation,
            $ia,
            $whatsapp,
            $agent
        );
    }

    public function resolveAudio(Conversation $conversation, array $payload, bool $fromMe)
    {
        $store = $conversation->store;
        $audioId = $payload['value']['messages'][0]['audio']['id'] ?? null;

        if (empty($audioId)) {
            return;
        }

        if (
            in_array($conversation->status, [
                ConversationStatusEnum::HUMAN,
                ConversationStatusEnum::CLOSED,
            ])
        ) {
            return;
        }

        if ($fromMe) {
            $conversation->update(['status' => ConversationStatusEnum::HUMAN]);

            ConversationMessage::create([
                'store_id' => $conversation->store_id,
                'conversation_id' => $conversation->id,
                'sender_type' => ConversationSenderTypeEnum::HUMAN,
                'content' => '[Áudio enviado pelo humano]',
                'payload' => $payload,
                'sent_at' => now(),
            ]);

            return;
        }

        $conversationMessage = ConversationMessage::create([
            'store_id' => $conversation->store_id,
            'conversation_id' => $conversation->id,
            'sender_type' => ConversationSenderTypeEnum::CUSTOMER,
            'content' => '[Áudio recebido]', // Vai ser atualizado depois da transcrição
            'payload' => $payload,
            'sent_at' => now(),
        ]);

        $ia = $store->integrations()
           ->where('type', IntegrationTypeEnum::AI)
           ->where('is_active', true)
           ->first();

        $agent = $store->activeAgent();

        TranscribeAudioMessageJob::dispatch(
            $conversationMessage,
            $conversation,
            $ia,
            $agent
        );
    }

    private function isMessageFromAi(Conversation $conversation, string $message): bool
    {
        return ConversationMessage::where('conversation_id', $conversation->id)
            ->where('sender_type', ConversationSenderTypeEnum::AI)
            ->where('content', $message)
            ->exists();
    }
}
