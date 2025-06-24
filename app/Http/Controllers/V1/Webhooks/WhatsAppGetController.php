<?php

declare(strict_types = 1);

namespace App\Http\Controllers\V1\Webhooks;

use App\Enums\IntegrationTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppGetController extends Controller
{
    public function __invoke(Request $request, string $webhook): \Illuminate\Http\Response
    {
        $integration = Integration::where('webhook', $webhook)
            ->where('type', IntegrationTypeEnum::WHATSAPP)
            ->firstOrFail();

        $payload = $request->all();

        Log::info('WhatsApp webhook recebido', $payload);

        if ($request->isMethod('get')) {
            $mode = $request->get('hub_mode');
            $token = $request->get('hub_verify_token');
            $challenge = $request->get('hub_challenge');

            if ($mode === 'subscribe' && $token === $integration->webhook) {
                $integration->update(['is_active' => true]);

                return response($challenge, 200);
            }

            return response('Forbidden', 403);
        }

        return response('OK', 200);
    }
}
