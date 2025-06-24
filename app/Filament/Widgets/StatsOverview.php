<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use App\Models\AbandonedCart;
use App\Models\ConversationMessage;
use App\Models\Customer;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = -1;

    // protected int | string | array $columnSpan = '1';

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $storeId = Filament::getTenant()->id;

        $customers = Customer::where('store_id', $storeId)->count();
        $carts = AbandonedCart::where('store_id', $storeId)->count();
        $chats = ConversationMessage::where('store_id', $storeId)->count();

        return [
            Stat::make('Clientes', $customers),

            Stat::make('Checkouts abandonados', $carts),

            Stat::make('Troca de mensagens', $chats),
        ];
    }
}
