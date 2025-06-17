<?php

declare(strict_types = 1);

namespace App\Filament\Resources\AbandonedCartResource\Pages;

use App\Filament\Resources\AbandonedCartResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAbandonedCart extends CreateRecord
{
    protected static string $resource = AbandonedCartResource::class;
}
