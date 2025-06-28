<?php

declare(strict_types = 1);

namespace App\Jobs\Shopify;

use App\Enums\CartStatusEnum;
use App\Enums\ConversationStatusEnum;
use App\Jobs\CheckExistsPhoneJob;
use App\Models\AbandonedCart;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Integration;
use App\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCheckoutJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Store $store,
        public Integration $integration,
        public array $checkout
    ) {
    }

    public function handle(): void
    {
        try {
            $checkout = $this->checkout;
            $shopifyCustomer = $checkout['customer'] ?? [];

            // Adaptação para GraphQL: verificar se há email no customer ou no checkout
            $customerEmail = $shopifyCustomer['email'] ?? null;
            $checkoutEmail = $checkout['email'] ?? null; // Caso o checkout tenha email direto

            if (!$customerEmail && !$checkoutEmail) {
                Log::warning("[ProcessCheckoutJob] Ignorando checkout sem e-mail. Store: {$this->store->id}, Checkout ID: " . ($checkout['id'] ?? 'N/A'));

                return;
            }

            // Extrair ID do cliente do formato GraphQL (gid://shopify/Customer/123 -> 123)
            $externalCustomerId = null;

            if (isset($shopifyCustomer['id'])) {
                $externalCustomerId = $this->extractShopifyId($shopifyCustomer['id']);
            }

            $customer = Customer::updateOrCreate([
                'store_id' => $this->store->id,
                'external_id' => $externalCustomerId,
            ], [
                'name' => trim(($shopifyCustomer['firstName'] ?? '') . ' ' . ($shopifyCustomer['lastName'] ?? '')),
                'email' => $customerEmail ?? $checkoutEmail,
                'phone' => $shopifyCustomer['phone'] ?? $this->extractPhoneFromAddresses($checkout),
                'whatsapp' => $shopifyCustomer['phone'] ?? $this->extractPhoneFromAddresses($checkout),
            ]);

            CheckExistsPhoneJob::dispatch($customer);

            // Extrair token do checkout do ID GraphQL
            $checkoutToken = $this->extractCheckoutToken($checkout);

            // Adaptar totalPrice para o formato GraphQL
            $totalAmount = null;

            if (isset($checkout['totalPrice']['amount'])) {
                $totalAmount = $checkout['totalPrice']['amount'];
            }

            $abandonedCart = AbandonedCart::updateOrCreate([
                'store_id' => $this->store->id,
                'external_cart_id' => $checkoutToken,
            ], [
                'customer_id' => $customer->id,
                'customer_data' => $shopifyCustomer,
                'cart_data' => $checkout,
                'total_amount' => $totalAmount,
                'status' => CartStatusEnum::ABANDONED,
            ]);

            $wasRecentlyCreated = $abandonedCart->wasRecentlyCreated;

            if ($wasRecentlyCreated) {
                $conversation = Conversation::create([
                    'store_id' => $this->store->id,
                    'abandoned_cart_id' => $abandonedCart->id,
                    'status' => ConversationStatusEnum::OPEN,
                    'started_at' => now(),
                ]);

                SendTemplateConversationMessageJob::dispatch(
                    $this->store,
                    $this->integration,
                    $customer,
                    $conversation
                );
            }

            Log::info("[ProcessCheckoutJob] Checkout processado com sucesso. Store: {$this->store->id}, Customer: {$customer->id}, Cart: {$abandonedCart->id}");
        } catch (\Exception $e) {
            Log::error("[ProcessCheckoutJob] Erro ao processar checkout: {$e->getMessage()}", [
                'store_id' => $this->store->id,
                'checkout_id' => $checkout['id'] ?? 'N/A',
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Extrai o ID numérico do formato GraphQL ID
     * gid://shopify/Customer/123456789 -> 123456789
     */
    private function extractShopifyId(string $graphqlId): ?string
    {
        if (preg_match('/\/(\d+)$/', $graphqlId, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extrai o token do checkout a partir do ID GraphQL
     * gid://shopify/AbandonedCheckout/123456789 -> 123456789
     * Ou usa um campo específico se disponível
     */
    private function extractCheckoutToken(array $checkout): string
    {
        // Se houver um campo token específico, use-o
        if (isset($checkout['token'])) {
            return (string) $checkout['token'];
        }

        // Caso contrário, extraia do ID GraphQL
        if (isset($checkout['id'])) {
            $extractedId = $this->extractShopifyId($checkout['id']);

            return $extractedId ?? $checkout['id'];
        }

        // Fallback - usar o ID completo
        return $checkout['id'] ?? uniqid();
    }

    /**
     * Extrai telefone dos endereços de cobrança ou entrega
     */
    private function extractPhoneFromAddresses(array $checkout): ?string
    {
        // Verificar endereço de cobrança
        if (isset($checkout['billingAddress']['phone']) && !empty($checkout['billingAddress']['phone'])) {
            return $checkout['billingAddress']['phone'];
        }

        // Verificar endereço de entrega
        if (isset($checkout['shippingAddress']['phone']) && !empty($checkout['shippingAddress']['phone'])) {
            return $checkout['shippingAddress']['phone'];
        }

        return null;
    }
}
