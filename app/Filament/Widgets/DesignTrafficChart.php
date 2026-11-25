<?php

namespace App\Filament\Widgets;

use App\Models\DesignDailyView;
use Filament\Widgets\LineChartWidget;
use Illuminate\Support\Number;
use IntlDateFormatter;

class DesignTrafficChart extends LineChartWidget
{
    private const DAYS = 14;

    protected static ?int $sort = 25;

    protected static ?bool $isLazyLoaded = false;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '20rem';

    public function getHeading(): string
    {
        return __('admin.dashboard.traffic');
    }

    public function getDescription(): string
    {
        return __('admin.dashboard.traffic_summary', [
            'views' => Number::format(array_sum($this->getCachedData()['datasets'][0]['data']), locale: 'fa'),
            'days' => Number::format(self::DAYS, locale: 'fa'),
        ]);
    }

    public function getChartAssistiveContent(): string
    {
        $chart = $this->getCachedData();

        return collect($chart['labels'])->map(fn (string $date, int $index): string => __('admin.dashboard.traffic_row', [
            'date' => $date,
            'views' => Number::format($chart['datasets'][0]['data'][$index], locale: 'fa'),
        ]))->implode(' ');
    }

    protected function getData(): array
    {
        $today = DesignDailyView::today();
        $start = $today->subDays(self::DAYS - 1);
        $dailyViews = DesignDailyView::query()
            ->whereBetween('viewed_on', [$start->toDateString(), $today->toDateString()])
            ->groupBy('viewed_on')
            ->selectRaw('viewed_on, sum(views) as total')
            ->pluck('total', 'viewed_on');

        $formatter = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::NONE, IntlDateFormatter::NONE,
            DesignDailyView::TIMEZONE, IntlDateFormatter::TRADITIONAL, 'd MMM');
        $labels = [];
        $counts = [];
        for ($date = $start; $date <= $today; $date = $date->addDay()) {
            $labels[] = $formatter->format($date);
            $counts[] = (int) $dailyViews->get($date->toDateString(), 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => __('admin.dashboard.daily_views'),
                'data' => $counts,
                'fill' => true,
            ]],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'locale' => 'fa-IR',
            'scales' => [
                'x' => ['reverse' => true, 'ticks' => ['maxTicksLimit' => 5, 'maxRotation' => 0]],
                'y' => ['position' => 'right', 'beginAtZero' => true, 'suggestedMax' => 1, 'ticks' => ['precision' => 0]],
            ],
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => ['rtl' => true, 'textDirection' => 'rtl'],
            ],
        ];
    }
}
