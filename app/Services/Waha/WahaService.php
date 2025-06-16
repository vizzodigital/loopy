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
        $url = $this->baseUrl . 'check-exists' . '?phone=' . $phone . '&session=' . $this->session;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Api-Key' => $this->token,
        ])->get($url);

        return $response->json();
    }
}
