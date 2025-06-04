<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Jobs\SendCartRecoveryMessageJob;
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

        if ($integration->secret && !$this->verifyWebhookSignature($request, $integration)) {
            Log::warning('Invalid webhook signature', [
                'webhook' => $webhook,
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $integration->activate();

        $payload = $request->all();

        Log::info('Webhook received', [
            'webhook' => $webhook,
            'store_id' => $integration->store_id,
            'platform_id' => $integration->platform_id,
            'payload' => $payload,
        ]);

        try {
            $this->processWebhookData($integration, $request->all());

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'webhook' => $webhook,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Processing failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function verifyWebhookSignature(Request $request, Integration $integration)
    {
        switch ($integration->platform_id) {
            case 1: // Yampi
                return $this->verifyYampiWebhook($request, $integration->secret_key);
            case 2: // Shopify
                return $this->verifyShopifyWebhook($request, $integration->secret_key);
            case 3: // WooCommerce
                return $this->verifyWooCommerceWebhook($request, $integration->secret_key);
            case 4: // WooCommerce
                return $this->verifyPandaStoreWebhook($request, $integration->secret_key);
            default:
                return true; // verifica ou não
        }
    }

    private function verifyYampiWebhook(Request $request, string $secret)
    {
        $signature = $request->header('X-Yampi-Signature');
        $payload = $request->getContent();

        $calculatedSignature = hash_hmac('sha256', $payload, $secret);

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

    private function verifyPandaStoreWebhook(Request $request, string $secret)
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
                $this->processYampiWebhook($integration, $payload);

                break;
            case 2:
                $this->processShopifyWebhook($integration, $payload);

                break;
            case 3:
                $this->processWooCommerceWebhook($integration, $payload);

                break;
            case 4:
                $this->processPandaStoreWebhook($integration, $payload);

                break;
            default:
                $this->processGenericWebhook($integration, $payload);
        }
    }

    private function processShopifyWebhook(Integration $integration, array $payload)
    {
        // ...
    }

    private function processWooCommerceWebhook(Integration $integration, array $payload)
    {
        // ...
    }

    private function processYampiWebhook(Integration $integration, array $payload)
    {
        $customerName = $payload['customer']['name'] ?? 'customer';
        $phoneNumber = $payload['customer']['phone'] ?? null;
        $cartItems = $payload['cart']['items'] ?? [];

        $productNames = collect($cartItems)->pluck('name')->implode(', ');

        if (!$phoneNumber || empty($cartItems)) {
            Log::warning('Cart has no phone or items, skipping.', compact('payload'));

            return;
        }

        dispatch(new SendCartRecoveryMessageJob(
            store: $integration->store,
            customerName: $customerName,
            phoneNumber: $phoneNumber,
            productList: $productNames
        ));
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
