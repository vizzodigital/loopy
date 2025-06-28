<?php

declare(strict_types = 1);

namespace App\Jobs\Shopify;

use App\Models\Integration;
use App\Models\Store;
use App\Services\Shopify\ShopifyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GetCheckoutsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Store $store,
        public Integration $integration
    ) {
    }

    public function handle(): void
    {
        try {
            $service = new ShopifyService($this->integration);

            // Usando o método que busca todos os checkouts com paginação automática
            $checkouts = $service->getAllAbandonedCheckouts();

            Log::info("[GetCheckoutsJob] Encontrados " . count($checkouts) . " checkouts abandonados para loja {$this->store->id}: {$this->store->name}");

            foreach ($checkouts as $checkout) {
                ProcessCheckoutJob::dispatch($this->store, $this->integration, $checkout);
            }

            Log::info("[GetCheckoutsJob] Checkout sync finalizada para loja {$this->store->id}: {$this->store->name}");
        } catch (\Exception $e) {
            Log::error("[GetCheckoutsJob] Falha: {$e->getMessage()}");
        }
    }
}
