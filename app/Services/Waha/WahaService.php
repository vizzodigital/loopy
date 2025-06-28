<?php

declare(strict_types = 1);

namespace App\Services\Waha;

use Illuminate\Support\Facades\Http;

class WahaService
{
    protected $baseUrl;

    protected $token;

    protected $session;

    public function __construct()
    {
        $this->baseUrl = config('services.waha.base_url');
        $this->token = config('services.waha.token');
        $this->session = config('services.waha.session');
    }

    public function checkExists(string $phone): array
    {
        $url = $this->baseUrl . '/api/contacts/check-exists' . '?phone=' . $phone . '&session=' . $this->session;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Api-Key' => $this->token,
        ])->get($url);

        return $response->json();
    }

    public function sendText(string $phone, string $message): array
    {
        $url = $this->baseUrl . '/api/sendText';

        $payload = [
            'chatId' => $phone . '@c.us',
            'text' => $message,
            'session' => $this->session,
        ];

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'X-Api-Key' => $this->token,
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        return $response->json();
    }
}
