<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class Infynia extends Widget
{
    protected static ?int $sort = -2;

    protected int | string | array $columnSpan = '1';

    protected static bool $isLazy = false;

    protected static string $view = 'filament.widgets.infynia';
}
