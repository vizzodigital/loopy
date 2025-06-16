<?php

declare(strict_types = 1);

namespace App\Services\CartRecovery\Platforms;

use App\Services\CartRecovery\Platforms\Contracts\PlatformPromptBuilderInterface;

class YampiPromptBuilder implements PlatformPromptBuilderInterface
{
    public function buildSystemPrompt(array $payload): string
    {
        $cart = $payload['resource'];
        $customer = $cart['customer']['data'];
        $items = collect($cart['items']['data'])->map(fn ($item) => $item['sku']['data']['title'])->implode(', ');

        return <<<PROMPT
            Informações do cliente:
            - Nome: {$customer['name']}
            - Telefone: {$customer['phone']['formated_number']}
            - WhatsApp: {$customer['phone']['whatsapp_link']}
            - Email: {$customer['email']}
            - CPF: {$customer['cpf']}

            Dados do carrinho:
            - Data do abandono: {$cart['created_at']['date']}
            - Valor total: {$cart['totalizers']['total_formated']}
            - Itens: {$items}
            - Frete: {$cart['shipping_service']}
            - Link para concluir o pedido: {$cart['simulate_url']}
        PROMPT;
    }

    public function extractCustomerName(array $payload): string
    {
        return $payload['resource']['customer']['data']['name'] ?? 'Cliente';
    }

    public function extractCartLink(array $payload): string
    {
        return $payload['resource']['simulate_url'] ?? '';
    }

    public function extractCartItems(array $payload): array
    {
        return collect($payload['resource']['items']['data'])
            ->map(fn ($item) => [
                'title' => $item['sku']['data']['title'],
                'quantity' => $item['quantity'],
                'price' => number_format($item['price'] / 100, 2, ',', '.'),
            ])->toArray();
    }

    public function extractCartValue(array $payload): string
    {
        return $payload['resource']['totalizers']['total_formated'] ?? '';
    }
}
