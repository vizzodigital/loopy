<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Shopify;

use App\Enums\IntegrationTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OAuthCallbackController extends Controller
{
    public function __invoke(Request $request)
    {
        $code = $request->query('code');
        $hmac = $request->query('hmac');
        $shop = $request->query('shop');
        $timestamp = $request->query('timestamp');

        // Validação do HMAC
        $params = $request->except(['hmac']);
        ksort($params);
        $queryString = urldecode(http_build_query($params));

        $calculatedHmac = hash_hmac('sha256', $queryString, (string) config('services.shopify.client_secret'));

        if (!hash_equals($hmac, $calculatedHmac)) {
            return response('HMAC validation failed', 400);
        }

        // Troca pelo access token
        // $response = Http::post("https://{$shop}/admin/oauth/access_token", [
        //     'client_id' => config('services.shopify.client_id'),
        //     'client_secret' => config('services.shopify.client_secret'),
        //     'code' => $code,
        // ]);

        $state = Str::uuid();

        $installUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
            'client_id' => config('services.shopify.client_id'),
            'scope' => 'read_orders,read_customers',
            'redirect_uri' => route('filament.admin.tenant'),
            'state' => $state,
            'grant_options[]' => 'per-user',
        ]);

        session(['state' => $state]);

        return redirect()->to($installUrl);

        // if (!$response->ok()) {
        //     Log::error('Shopify token error', ['response' => $response->body()]);

        //     return response('Token request failed', 500);
        // }

        // $data = $response->json();

        // Log::info('Shopify installed', [
        //     'shop' => $shop,
        //     'access_token' => $data['access_token'],
        //     'scope' => $data['scope'],
        // ]);

        // $integration = Integration::where('store_id', Filament::getTenant()->id)
        //     ->where('platform_id', 2)
        //     ->where('type', IntegrationTypeEnum::ECOMMERCE)
        //     ->first();

        // $integration->update([
        //     'configs' => [
        //         'shop' => $data['scope'],
        //         'access_token' => $data['access_token'],
        //         'scope' => $data['scope'],
        //     ],
        //     'is_active' => true,
        // ]);
    }
}
