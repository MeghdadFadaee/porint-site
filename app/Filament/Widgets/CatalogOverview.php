<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Collections\CollectionResource;
use App\Filament\Resources\Designs\DesignResource;
use App\Filament\Resources\GarmentTemplates\GarmentTemplateResource;
use App\Models\Collection;
use App\Models\Design;
use App\Models\GarmentTemplate;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class CatalogOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    protected static ?bool $isLazyLoaded = false;

    protected ?string $pollingInterval = null;

    protected int|array|null $columns = ['default' => 2, '@xl' => 3];

    protected function getHeading(): string
    {
        return __('admin.dashboard.overview');
    }

    protected function getStats(): array
    {
        $designs = Design::query()
            ->selectRaw('count(*) as total, count(*) - count(published_at) as drafts')
            ->selectSub(Design::visible()->selectRaw('count(*)'), 'visible')
            ->toBase()
            ->first();

        return [
            Stat::make(__('admin.dashboard.total_designs'), Number::format($designs->total, locale: 'fa'))
                ->icon(Heroicon::OutlinedSwatch)->url(DesignResource::getUrl('index')),
            Stat::make(__('admin.dashboard.visible_designs'), Number::format($designs->visible, locale: 'fa'))
                ->icon(Heroicon::OutlinedGlobeAlt),
            Stat::make(__('admin.dashboard.drafts'), Number::format($designs->drafts, locale: 'fa'))
                ->icon(Heroicon::OutlinedPencilSquare),
            Stat::make(__('admin.dashboard.hidden_designs'), Number::format($designs->total - $designs->visible - $designs->drafts, locale: 'fa'))
                ->icon(Heroicon::OutlinedEyeSlash)
                ->description(__('admin.dashboard.hidden_description')),
            Stat::make(__('admin.collections.plural'), Number::format(Collection::count(), locale: 'fa'))
                ->icon(Heroicon::OutlinedRectangleStack)->url(CollectionResource::getUrl('index')),
            Stat::make(__('admin.garments.plural'), Number::format(GarmentTemplate::count(), locale: 'fa'))
                ->icon(Heroicon::OutlinedPhoto)->url(GarmentTemplateResource::getUrl('index')),
        ];
    }
}
