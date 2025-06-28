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

class GetCheckoutsBatchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Store $store,
        public Integration $integration,
        public int $batchSize = 25
    ) {
    }

    public function handle(): void
    {
        try {
            $service = new ShopifyService($this->integration);
            $allCheckouts = $service->getAllAbandonedCheckouts();

            // Processar em lotes
            $batches = array_chunk($allCheckouts, $this->batchSize);

            Log::info("[GetCheckoutsBatchJob] Processando " . count($allCheckouts) . " checkouts em " . count($batches) . " lotes para loja {$this->store->id}");

            foreach ($batches as $batchIndex => $batch) {
                foreach ($batch as $checkout) {
                    ProcessCheckoutJob::dispatch($this->store, $this->integration, $checkout)
                        ->delay(now()->addSeconds($batchIndex * 2)); // delay entre lotes
                }
            }

            Log::info("[GetCheckoutsBatchJob] Todos os lotes despachados para loja {$this->store->id}: {$this->store->name}");
        } catch (\Exception $e) {
            Log::error("[GetCheckoutsBatchJob] Falha: {$e->getMessage()}");
        }
    }
}
