<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Shopify;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OAuthAccessTokenController extends Controller
{
    public function __invoke(Request $request)
    {
        $hmac = $request->query('hmac');
        $shop = $request->query('shop');
        $code = $request->query('code');

        $params = $request->except(['hmac']);
        ksort($params);
        $queryString = urldecode(http_build_query($params));
        $calculatedHmac = hash_hmac('sha256', $queryString, (string) config('services.shopify.client_secret'));

        if (!hash_equals($hmac, $calculatedHmac)) {
            return response('HMAC validation failed', 400);
        }

        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => config('services.shopify.client_id'),
            'client_secret' => config('services.shopify.client_secret'),
            'code' => $code,
        ]);

        $data = $response->json();

        Log::info('Shopify installed', [
            'shop' => $shop,
            'access_token' => $data['access_token'],
            'scope' => $data['scope'],
        ]);

        $integration = Integration::whereJsonContains('configs->shop', 'infyniashop.myshopify.com')->first();

        $integration->update([
            'configs' => [
                'shop' => $shop,
                'access_token' => $data['access_token'],
                'scope' => $data['scope'],
                'user' => $data['associated_user']['email'] ?? null,
            ],
            'is_active' => true,
        ]);

        return redirect()->route('filament.admin.resources.integrations.index');
    }
}
