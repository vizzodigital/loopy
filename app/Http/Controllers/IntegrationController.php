<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Models\Integration;

class IntegrationController extends Controller
{
    public function status(Integration $integration)
    {
        return response()->json([
            'is_active' => $integration->is_active,
            'first_webhook_at' => $integration->first_webhook_at,
            'last_webhook_at' => $integration->last_webhook_at,
            'webhook_count' => $integration->webhook_logs_count ?? 0, // se você tiver logs
        ]);
    }
}
