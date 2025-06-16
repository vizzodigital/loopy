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

class WebhookWhatsAppZApiController extends Controller
{
    public function __invoke(Request $request, string $webhook)
    {
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

        $integration->activate();

        $data = $request->json()->all();

        try {
            $test = $this->processWebhookData($integration, $data);

            return response()->json(['status' => $test], Response::HTTP_OK);
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
        switch ($integration->platform_id) {
            case 7:
                return $this->processWhatsAppWebhook($integration, $payload);

                break;
            case 8:
                return $this->processZapiWebhook($integration, $payload);

                break;
            case 9:
                return $this->processWahaWebhook($integration, $payload);

                break;
            default:
                return $this->processGenericWebhook($integration, $payload);
        }
    }

    private function processWhatsAppWebhook()
    {
        //
    }

    private function processZapiWebhook(Integration $integration, array $payload)
    {
        $fromMe = (bool)($payload['fromMe'] ?? false);
        $whatsapp = $payload['phone'] ?? null;

        if (!$whatsapp) {
            throw new \Exception('Phone not found in payload');
        }

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

        $type = $this->determineType($payload);

        if (!$type) {
            return;
        }

        match ($type) {
            'text' => $this->resolveText($conversation, $payload, $fromMe),
            'audio' => $this->resolveAudio($conversation, $payload, $fromMe),
            default => null,
        };
    }

    private function processWahaWebhook()
    {
        //
    }

    private function processGenericWebhook()
    {
        //
    }

    private function determineType(array $data): ?string
    {
        foreach (['text', 'audio'] as $type) {
            if (!empty($data[$type])) {
                return $type;
            }
        }

        return null;
    }

    public function resolveText(Conversation $conversation, array $payload, bool $fromMe)
    {
        $message = trim($payload['text']['message'] ?? '');

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
        $audioUrl = $payload['audio']['audioUrl'] ?? null;

        if (empty($audioUrl)) {
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
            ->where('sender_type', 'ai')
            ->where('content', $message)
            ->exists();
    }
}
