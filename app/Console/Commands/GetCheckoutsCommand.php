<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use App\Enums\IntegrationTypeEnum;
use App\Enums\PlatformsEnum;
use App\Jobs\Shopify\GetCheckoutsJob;
use App\Models\Integration;
use Illuminate\Console\Command;

class GetCheckoutsCommand extends Command
{
    protected $signature = 'shopify:get-checkouts';

    protected $description = 'Dispara o job de consulta de checkouts abandonados para integrações ativas Shopify';

    public function handle(): int
    {
        $integrations = Integration::with('store')
                          ->where('type', IntegrationTypeEnum::ECOMMERCE)
                          ->where('platform_id', PlatformsEnum::SHOPIFY->value)
                          ->where('is_active', true)
                          ->get();

        foreach ($integrations as $integration) {
            dispatch(new GetCheckoutsJob($integration->store, $integration));
        }

        $this->info('Jobs de consulta de checkouts Shopify despachados.');

        return Command::SUCCESS;
    }
}
