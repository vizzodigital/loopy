<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use App\Models\AbandonedCart;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class AbandonedCartsChart extends ChartWidget
{
    protected static ?string $heading = 'Checkouts abandonados';

    protected static string $color = 'info';

    protected static ?string $maxHeight = '300px';

    protected static ?int $sort = 0;

    protected static bool $isLazy = true;

    public ?string $filter = 'week';

    private array $filterConfigs = [
        'today' => [
            'start_method' => 'startOfDay',
            'sql_format' => '%H',
            'label_format' => 'H',
            'step_method' => 'addHour',
            'period' => 'hours',
            'end_method' => 'endOfDay',
        ],
        'week' => [
            'start_method' => 'startOfWeek',
            'sql_format' => '%Y-%m-%d',
            'label_format' => 'd/m',
            'step_method' => 'addDay',
            'period' => 'days',
            'end_method' => 'endOfWeek',
        ],
        'month' => [
            'start_method' => 'startOfMonth',
            'sql_format' => '%Y-%m-%d',
            'label_format' => 'd/m',
            'step_method' => 'addDay',
            'period' => 'days',
            'end_method' => 'endOfMonth',
        ],
        'year' => [
            'start_method' => 'startOfYear',
            'sql_format' => '%Y-%m',
            'label_format' => 'pt_br_month',
            'step_method' => 'addMonth',
            'period' => 'months',
            'end_method' => 'endOfYear',
        ],
    ];

    private array $monthsInPortuguese = [
        1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr',
        5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
        9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez',
    ];

    protected function getData(): array
    {
        $storeId = Filament::getTenant()->id;
        $config = $this->getFilterConfig();

        $periodData = $this->generatePeriodData($config);
        $results = $this->fetchChartData($storeId, $config, $periodData['start'], $periodData['end']);

        return [
            'datasets' => [
                [
                    'label' => 'Checkouts abandonados',
                    'data' => $this->mapDataToLabels($results, $periodData['labels'], $config),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $periodData['labels'],
        ];
    }

    private function getFilterConfig(): array
    {
        return $this->filterConfigs[$this->filter] ?? $this->filterConfigs['week'];
    }

    private function generatePeriodData(array $config): array
    {
        $start = CarbonImmutable::now()->{$config['start_method']}();
        $end = CarbonImmutable::now()->{$config['end_method']}();

        $period = match ($config['period']) {
            'hours' => CarbonPeriod::create($start, '1 hour', $end),
            'days' => CarbonPeriod::create($start, '1 day', $end),
            'months' => CarbonPeriod::create($start, '1 month', $end),
            default => CarbonPeriod::create($start, '1 day', $end),
        };

        $labels = [];
        $keys = [];

        foreach ($period as $date) {
            $immutableDate = $date instanceof CarbonImmutable ? $date : CarbonImmutable::parse($date);

            $labels[] = $this->formatLabel($immutableDate, $config['label_format']);
            $keys[] = $immutableDate->format($this->getSqlFormatForKey($config['sql_format']));
        }

        return [
            'start' => $start,
            'end' => $end,
            'labels' => $labels,
            'keys' => $keys,
        ];
    }

    private function formatLabel($date, string $format): string
    {
        if (!$date instanceof CarbonImmutable) {
            $date = CarbonImmutable::parse($date);
        }

        return match ($format) {
            'pt_br_month' => $this->monthsInPortuguese[$date->month] ?? $date->format('M'),
            default => $date->format($format),
        };
    }

    private function getSqlFormatForKey(string $sqlFormat): string
    {
        return match ($sqlFormat) {
            '%H' => 'H',
            '%Y-%m-%d' => 'Y-m-d',
            '%Y-%m' => 'Y-m',
            default => 'Y-m-d',
        };
    }

    private function fetchChartData(int $storeId, array $config, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return AbandonedCart::query()
            ->selectRaw("DATE_FORMAT(created_at, '{$config['sql_format']}') as date_key, COUNT(*) as total")
            ->where('store_id', $storeId)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date_key')
            ->orderBy('date_key')
            ->pluck('total', 'date_key');
    }

    private function mapDataToLabels(Collection $results, array $labels, array $config): array
    {
        $data = [];
        $keyFormat = $this->getSqlFormatForKey($config['sql_format']);

        foreach ($labels as $index => $label) {
            $key = $this->getLabelKey($label, $config, $keyFormat);
            $data[] = $results->get($key, 0);
        }

        return $data;
    }

    private function getLabelKey(string $label, array $config, string $keyFormat): string
    {
        return match ($config['sql_format']) {
            '%H' => str_pad($label, 2, '0', STR_PAD_LEFT),
            '%Y-%m-%d' => $this->convertDayMonthToKey($label),
            '%Y-%m' => $this->convertMonthToKey($label),
            default => $label,
        };
    }

    private function convertDayMonthToKey(string $label): string
    {
        $parts = explode('/', $label);

        if (count($parts) !== 2) {
            return now()->format('Y-m-d');
        }

        $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
        $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);

        return now()->format('Y') . "-{$month}-{$day}";
    }

    private function convertMonthToKey(string $label): string
    {
        try {
            $monthNumber = array_search($label, $this->monthsInPortuguese);

            if ($monthNumber !== false) {
                $month = str_pad((string) $monthNumber, 2, '0', STR_PAD_LEFT);

                return now()->format('Y') . "-{$month}";
            }

            $month = CarbonImmutable::parse("1 {$label} " . now()->year)->format('m');

            return now()->format('Y') . "-{$month}";
        } catch (\Exception $e) {
            return now()->format('Y-m');
        }
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hoje',
            'week' => 'Última semana',
            'month' => 'Último mês',
            'year' => 'Este ano',
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
