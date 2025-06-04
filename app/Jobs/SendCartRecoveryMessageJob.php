<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Models\Store;
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
        protected Store $store,
        protected string $customerName,
        protected string $phoneNumber,
        protected string $productList,
    ) {
        //
    }

    public function handle(): void
    {
        $prompt = "Create a friendly and persuasive message to recover an abandoned shopping cart. "
                . "The customer's name is {$this->customerName} and the cart contains: {$this->productList}. "
                . "Offer a 10% discount if they complete the purchase today. Keep the tone casual and concise.";

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
