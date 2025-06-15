<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\AbandonmentReason;
use Illuminate\Database\Seeder;

class AbandonmentReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            [
                'name' => 'price_concern',
                'description' => 'Preocupação com preço',
            ],
            [
                'name' => 'product_question',
                'description' => 'Pergunta sobre o produto',
            ],
            [
                'name' => 'shipping_doubt',
                'description' => 'Pergunta sobre envio ou entrega',
            ],
            [
                'name' => 'payment_issue',
                'description' => 'Problema com o pagamento',
            ],
            [
                'name' => 'technical_difficulty',
                'description' => 'Dificuldade técnica',
            ],
            [
                'name' => 'discount_request',
                'description' => 'Solicitação de Desconto',
            ],
            [
                'name' => 'trust_issue',
                'description' => 'Problema com a confiança',
            ],
            [
                'name' => 'cart_confusion',
                'description' => 'Confusão no carrinho',
            ],
            [
                'name' => 'left_by_mistake',
                'description' => 'Saiu por engano',
            ],
            [
                'name' => 'other',
                'description' => 'Outro',
            ],
        ];

        foreach ($reasons as $reason) {
            AbandonmentReason::firstOrCreate($reason);
        }
    }
}
