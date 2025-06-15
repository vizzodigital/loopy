<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Enums\CartStatusEnum;
use App\Enums\ConversationStatusEnum;
use App\Enums\IntegrationTypeEnum;
use App\Jobs\SendFirstAbandonedCartMessageJob;
use App\Models\AbandonedCart;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookController extends Controller
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

        if (!$this->verifyWebhookSignature($request, $integration)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $integration->activate();

        try {
            $test = $this->processWebhookData($integration, $request->all());

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

    private function verifyWebhookSignature(Request $request, Integration $integration)
    {
        switch ($integration->platform_id) {
            case 1: // Yampi
                return $this->verifyYampiWebhook($request, $integration->configs['secret']);
            case 2: // Shopify
                return $this->verifyShopifyWebhook($request, $integration->secret_key);
            case 3: // CartPanda
                return $this->verifyCartPandaWebhook($request, $integration->secret_key);
            case 4: // WooCommerce
                return $this->verifyWooCommerceWebhook($request, $integration->secret_key);
            default:
                return true; // verifica ou não
        }
    }

    private function verifyYampiWebhook(Request $request, string $secret)
    {
        $signature = $request->header('X-Yampi-Hmac-SHA256');
        $payload = $request->getContent();

        $calculatedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        return hash_equals($signature, $calculatedSignature);
    }

    private function verifyShopifyWebhook(Request $request, string $secret)
    {
        $signature = $request->header('X-Shopify-Hmac-Sha256');
        $payload = $request->getContent();

        $calculatedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        return hash_equals($signature, $calculatedSignature);
    }

    private function verifyWooCommerceWebhook(Request $request, string $secret)
    {
        $signature = $request->header('VERIFICAR_KEY');
        $payload = $request->getContent();

        $calculatedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        return hash_equals($signature, $calculatedSignature);
    }

    private function verifyCartPandaWebhook(Request $request, string $secret)
    {
        $signature = $request->header('VERIFICAR_KEY');
        $payload = $request->getContent();

        $calculatedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        return hash_equals($signature, $calculatedSignature);
    }

    private function processWebhookData(Integration $integration, array $payload)
    {
        switch ($integration->platform_id) {
            case 1:
                return $this->processYampiWebhook($integration, $payload);

                break;
            case 2:
                return $this->processShopifyWebhook($integration, $payload);

                break;
            case 3:
                return $this->processPandaStoreWebhook($integration, $payload);

                break;
            case 4:
                return $this->processWooCommerceWebhook($integration, $payload);

                break;
            default:
                return $this->processGenericWebhook($integration, $payload);
        }
    }

    private function processYampiWebhook(Integration $integration, array $payload)
    {
        $store = $integration->store;

        $customerData = $payload['resource']['customer']['data'];

        $customer = Customer::updateOrCreate(
            [
                'store_id' => $store->id,
                'external_id' => $payload['resource']['customer_id'],
            ],
            [
                'name' => $customerData['full_name'] ?? trim($customerData['first_name'] . ' ' . $customerData['last_name']),
                'email' => $customerData['email'],
                'phone' => $customerData['phone']['formated_number'],
                'whatsapp' => $customerData['phone']['full_number'], // preenchido via API Waha exists
            ]
        );

        $cartData = [
            'id' => $payload['resource']['id'],
            'cart_url' => $payload['resource']['simulate_url'],
            'cart_total' => $payload['resource']['totalizers']['total'],
            'shipping' => $payload['resource']['totalizers']['shipment'],
            'shipping_service' => $payload['resource']['shipping_service'],
            'line_items' => $payload['resource']['spreadsheet'],
            'abandoned_step' => $payload['resource']['search']['data']['abandoned_step'],
            'created_at_origin' => $payload['resource'],
        ];

        $abandonedCart = AbandonedCart::updateOrCreate(
            [
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'external_cart_id' => $payload['resource']['id'],
            ],
            [
                'abandonment_reason_id' => null,
                'cart_data' => $cartData,
                'customer_data' => $customerData,
                'total_amount' => $payload['resource']['totalizers']['total'],
                'status' => CartStatusEnum::ABANDONED,
            ]
        );

        $ia = $store->integrations()
            ->where('type', IntegrationTypeEnum::AI)
            ->where('is_active', true)
            ->first();

        $agent = $store->activeAgent();

        $whatsapp = $store->integrations()
            ->where('type', IntegrationTypeEnum::WHATSAPP)
            ->first();

        $conversation = Conversation::updateOrCreate(
            [
                'store_id' => $store->id,
                'abandoned_cart_id' => $abandonedCart->id,
            ],
            [
                'store_id' => $store->id,
                'abandoned_cart_id' => $abandonedCart->id,
                'status' => ConversationStatusEnum::OPEN,
                'started_at' => now(),
            ]
        );

        SendFirstAbandonedCartMessageJob::dispatch(
            $conversation,
            $ia,
            $whatsapp,
            $agent
        );
    }

    private function processShopifyWebhook(Integration $integration, array $payload)
    {
        // ...
    }

    private function processWooCommerceWebhook(Integration $integration, array $payload)
    {
        // ...
    }

    private function processPandaStoreWebhook(Integration $integration, array $payload)
    {
        // ...
    }

    private function processGenericWebhook(Integration $integration, array $payload)
    {
        // ...
    }
}
