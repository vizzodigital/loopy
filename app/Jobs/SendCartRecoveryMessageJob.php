<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Models\Store;
use App\Services\Zapi\ZapiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OpenAI\Laravel\Facades\OpenAI;

class SendCartRecoveryMessageJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected ZapiService $whatsapp,
        protected Store $store,
        protected string $customerName,
        protected string $phoneNumber,
        protected string $productList,
    ) {
        //
    }

    public function handle(): void
    {
        $prompt = "Crie uma mensagem amigável e persuasiva para recuperar um carrinho de compras abandonado. "
                . "O nome do cliente é {$this->customerName} e o carrinho contém: {$this->productList}. "
                . "Ofereça um desconto de 10% se ele concluir a compra hoje. Mantenha o tom casual e conciso.";

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o', // or 'gpt-3.5-turbo' for lower cost
            'messages' => [
                ['role' => 'system', 'content' => 'You are a marketing expert writing messages for WhatsApp.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $message = $response['choices'][0]['message']['content'];

        // $this->sendWhatsAppMessage($this->phoneNumber, $message);
    }
}
