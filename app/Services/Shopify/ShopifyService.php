<?php

declare(strict_types = 1);

namespace App\Services\Shopify;

use App\Models\Integration;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    protected string $accessToken;

    protected string $shop;

    public function __construct(protected Integration $integration)
    {
        $this->accessToken = $integration->configs['access_token'] ?? throw new \Exception('Shopify access token not configured.');
        $this->shop = $integration->configs['shop'] ?? throw new \Exception('Shopify shop domain not configured.');
    }

    public function getAbandonedCheckouts(): array
    {
        $createdAtMin = now()->subMonth()->toIso8601String();
        $createdAtMax = now()->subHours(10)->toIso8601String();

        $query = '
            query GetAbandonedCheckouts($first: Int!, $createdAtMin: DateTime, $createdAtMax: DateTime, $after: String) {
                abandonedCheckouts(
                    first: $first
                    query: "created_at:>=' . $createdAtMin . ' AND created_at:<=' . $createdAtMax . '"
                    after: $after
                ) {
                    edges {
                        node {
                            id
                            abandonedCheckoutUrl
                            completedAt
                            createdAt
                            updatedAt
                            totalPrice {
                                amount
                                currencyCode
                            }
                            customer {
                                id
                                firstName
                                lastName
                                email
                            }
                            billingAddress {
                                firstName
                                lastName
                                address1
                                address2
                                city
                                province
                                country
                                zip
                                phone
                            }
                            shippingAddress {
                                firstName
                                lastName
                                address1
                                address2
                                city
                                province
                                country
                                zip
                                phone
                            }
                            lineItems(first: 250) {
                                edges {
                                    node {
                                        id
                                        title
                                        quantity
                                        variant {
                                            id
                                            title
                                            price {
                                                amount
                                                currencyCode
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        cursor
                    }
                    pageInfo {
                        hasNextPage
                        hasPreviousPage
                        startCursor
                        endCursor
                    }
                }
            }
        ';

        return $this->executeGraphQLQuery($query, [
            'first' => 50,
            'createdAtMin' => $createdAtMin,
            'createdAtMax' => $createdAtMax,
        ]);
    }

    public function getAllAbandonedCheckouts(): array
    {
        $allCheckouts = [];
        $cursor = null;
        $hasNextPage = true;

        while ($hasNextPage) {
            $result = $this->getAbandonedCheckoutsPaginated($cursor);

            if (empty($result['data']['abandonedCheckouts']['edges'])) {
                break;
            }

            $checkouts = $result['data']['abandonedCheckouts']['edges'];
            $allCheckouts = array_merge($allCheckouts, $checkouts);

            $pageInfo = $result['data']['abandonedCheckouts']['pageInfo'];
            $hasNextPage = $pageInfo['hasNextPage'];
            $cursor = $pageInfo['endCursor'];
        }

        return $this->filterAbandonedCheckouts($allCheckouts);
    }

    protected function getAbandonedCheckoutsPaginated(?string $cursor = null): array
    {
        $createdAtMin = now()->subMonth()->toIso8601String();
        $createdAtMax = now()->subHours(10)->toIso8601String();

        $query = '
            query GetAbandonedCheckouts($first: Int!, $after: String) {
                abandonedCheckouts(
                    first: $first
                    query: "created_at:>=' . $createdAtMin . ' AND created_at:<=' . $createdAtMax . '"
                    after: $after
                ) {
                    edges {
                        node {
                            id
                            abandonedCheckoutUrl
                            completedAt
                            createdAt
                            updatedAt
                            totalPrice {
                                amount
                                currencyCode
                            }
                            customer {
                                id
                                firstName
                                lastName
                                email
                            }
                            billingAddress {
                                firstName
                                lastName
                                address1
                                city
                                province
                                country
                                zip
                                phone
                            }
                            shippingAddress {
                                firstName
                                lastName
                                address1
                                city
                                province
                                country
                                zip
                                phone
                            }
                            lineItems(first: 10) {
                                edges {
                                    node {
                                        id
                                        title
                                        quantity
                                        variant {
                                            id
                                            title
                                            price {
                                                amount
                                                currencyCode
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    pageInfo {
                        hasNextPage
                        endCursor
                    }
                }
            }
        ';

        return $this->executeGraphQLQuery($query, [
            'first' => 50,
            'after' => $cursor,
        ]);
    }

    protected function executeGraphQLQuery(string $query, array $variables = []): array
    {
        $url = "https://{$this->shop}/admin/api/2025-04/graphql.json";

        $payload = [
            'query' => $query,
            'variables' => $variables,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $this->accessToken,
        ])->post($url, $payload);

        if (!$response->successful()) {
            Log::error('[ShopifyService::executeGraphQLQuery] Error: ' . $response->body());

            return [];
        }

        $data = $response->json();

        // Verificar se há erros GraphQL
        if (isset($data['errors'])) {
            Log::error('[ShopifyService::executeGraphQLQuery] GraphQL Errors: ' . json_encode($data['errors']));

            return [];
        }

        return $data;
    }

    protected function filterAbandonedCheckouts(array $checkouts): array
    {
        return collect($checkouts)
            ->map(fn ($edge) => $edge['node'])
            ->filter(function ($checkout) {
                // Filtrar apenas checkouts não completados
                if (!empty($checkout['completedAt'])) {
                    return false;
                }

                // Filtrar checkouts que não foram atualizados nos últimos 30 minutos
                $updatedAt = Carbon::parse($checkout['updatedAt']);

                return $updatedAt->lt(now()->subMinutes(30));
            })
            ->values()
            ->all();
    }

    // Método auxiliar para buscar um checkout específico por ID
    public function getAbandonedCheckoutById(string $checkoutId): ?array
    {
        $query = '
            query GetAbandonedCheckout($id: ID!) {
                abandonedCheckout(id: $id) {
                    id
                    abandonedCheckoutUrl
                    completedAt
                    createdAt
                    updatedAt
                    totalPrice {
                        amount
                        currencyCode
                    }
                    customer {
                        id
                        firstName
                        lastName
                        email
                    }
                    billingAddress {
                        firstName
                        lastName
                        address1
                        city
                        province
                        country
                        zip
                        phone
                    }
                    shippingAddress {
                        firstName
                        lastName
                        address1
                        city
                        province
                        country
                        zip
                        phone
                    }
                    lineItems(first: 250) {
                        edges {
                            node {
                                id
                                title
                                quantity
                                variant {
                                    id
                                    title
                                    price {
                                        amount
                                        currencyCode
                                    }
                                }
                            }
                        }
                    }
                }
            }
        ';

        $result = $this->executeGraphQLQuery($query, ['id' => $checkoutId]);

        return $result['data']['abandonedCheckout'] ?? null;
    }
}
