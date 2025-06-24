<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use App\Models\AbandonedCart;
use App\Models\AbandonmentReason;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class AbandonmentReasonChart extends ChartWidget
{
    protected static ?string $heading = 'Motivos do abandono';

    protected static ?int $sort = 1;

    // protected int | string | array $columnSpan = '1';

    protected static bool $isLazy = true;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $storeId = Filament::getTenant()->id;

        $data = AbandonedCart::query()
            ->where('store_id', $storeId)
            ->selectRaw('abandonment_reason_id, COUNT(*) as total')
            ->groupBy('abandonment_reason_id')
            ->get();

        $reasonIds = $data->pluck('abandonment_reason_id')->filter()->unique();

        $reasons = AbandonmentReason::whereIn('id', $reasonIds)
            ->pluck('description', 'id');

        $labels = [];
        $counts = [];

        foreach ($data as $item) {
            $labels[] = $reasons[$item->abandonment_reason_id] ?? 'Sem motivo';
            $counts[] = $item->total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Motivos do Abandono',
                    'data' => $counts,
                    'backgroundColor' => [
                        '#F87171', // vermelho
                        '#FBBF24', // amarelo
                        '#34D399', // verde
                        '#60A5FA', // azul
                        '#A78BFA', // roxo
                        '#F472B6', // rosa
                        '#FCD34D', // amarelo claro
                        '#4ADE80', // verde claro
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => true,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
        ];
    }
}
