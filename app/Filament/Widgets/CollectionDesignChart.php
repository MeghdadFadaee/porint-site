<?php

namespace App\Filament\Widgets;

use App\Models\Collection;
use Filament\Widgets\BarChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as Models;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class CollectionDesignChart extends BarChartWidget
{
    private const LIMIT = 8;

    protected static ?int $sort = 20;

    protected static ?bool $isLazyLoaded = false;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '20rem';

    /** @var Models<int, Collection>|null */
    private ?Models $collections = null;

    public function getHeading(): string
    {
        return __('admin.dashboard.collection_chart');
    }

    public function getDescription(): string
    {
        return __('admin.dashboard.chart_description', ['limit' => Number::format(self::LIMIT, locale: 'fa')]);
    }

    public function getEmptyStateHeading(): string
    {
        return __('admin.dashboard.empty_chart');
    }

    protected function getData(): array
    {
        $collections = $this->collections();

        if (! $collections->max('designs_count')) {
            return [];
        }

        return [
            'labels' => $collections->map(fn (Collection $collection): string => Str::limit($collection->name_fa, 22))->all(),
            'datasets' => [
                [
                    'label' => __('admin.dashboard.on_site'),
                    'data' => $collections->pluck('visible_count')->all(),
                    'backgroundColor' => '#ad263f',
                    'maxBarThickness' => 24,
                ],
                [
                    'label' => __('admin.dashboard.off_site'),
                    'data' => $collections->map(fn (Collection $collection): int => $collection->designs_count - $collection->visible_count)->all(),
                    'backgroundColor' => '#90827b',
                    'maxBarThickness' => 24,
                ],
            ],
        ];
    }

    public function getChartAssistiveContent(): string
    {
        return $this->collections()->map(fn (Collection $collection): string => __('admin.dashboard.chart_row', [
            'name' => $collection->name_fa,
            'visible' => Number::format($collection->visible_count, locale: 'fa'),
            'hidden' => Number::format($collection->designs_count - $collection->visible_count, locale: 'fa'),
        ]))->implode(' ');
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'locale' => 'fa-IR',
            'scales' => [
                'x' => ['stacked' => true, 'reverse' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0]],
                'y' => ['stacked' => true, 'position' => 'right', 'ticks' => ['autoSkip' => false]],
            ],
            'plugins' => [
                'legend' => ['rtl' => true, 'textDirection' => 'rtl'],
                'tooltip' => ['rtl' => true, 'textDirection' => 'rtl'],
            ],
        ];
    }

    /** @return Models<int, Collection> */
    private function collections(): Models
    {
        return $this->collections ??= Collection::query()
            ->withCount(['designs', 'designs as visible_count' => fn (Builder $query) => $query->visible()])
            ->orderByDesc('designs_count')
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get();
    }
}
