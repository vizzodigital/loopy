<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ShopifyTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Teste gerais do shopify';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shop = 'infyniashop.myshopify.com';
        $token = 'shpua_51bafa7b45644a5b6c89fd62bfc64429';

        $query = <<<'GRAPHQL'
        {
            products(first: 3) {
                edges {
                    node {
                    id
                    title
                    }
                }
            }
        }
        GRAPHQL;

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
            'Content-Type' => 'application/json',
        ])->post("https://{$shop}/admin/api/2025-04/graphql.json", [
            'query' => $query,
        ]);

        if ($response->successful()) {
            $this->info('Resposta recebida com sucesso!');
            dd($response->json());
        } else {
            $this->error('Erro na requisição:');
            dump($response->body());
        }

        return 0;
    }
}
