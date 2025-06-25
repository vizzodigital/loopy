<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Shopify;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OAuthCallbackController extends Controller
{
    public function __invoke(Request $request)
    {
        $hmac = $request->query('hmac');
        $shop = $request->query('shop');

        // Validação do HMAC
        $params = $request->except(['hmac']);
        ksort($params);
        $queryString = urldecode(http_build_query($params));
        $calculatedHmac = hash_hmac('sha256', $queryString, (string) config('services.shopify.client_secret'));

        if (!hash_equals($hmac, $calculatedHmac)) {
            return response('HMAC validation failed', 400);
        }

        $state = Str::uuid();

        $installUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
            'client_id' => config('services.shopify.client_id'),
            'scope' => 'read_orders,read_customers',
            'redirect_uri' => route('shopify.oauth.token'),
            'state' => $state,
            'grant_options[]' => 'per-user',
        ]);

        session(['state' => $state]);

        return redirect()->to($installUrl);
    }
}
