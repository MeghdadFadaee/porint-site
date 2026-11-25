<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Designs\DesignResource;
use App\Models\Design;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentDesigns extends TableWidget
{
    protected static ?int $sort = 30;

    protected static ?bool $isLazyLoaded = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin.dashboard.recent_designs'))
            ->query(Design::query()->with('collection')->withSum('dailyViews as page_views', 'views')
                ->latest('updated_at')->orderByDesc('id')->limit(6))
            ->paginated(false)
            ->columns([
                TextColumn::make('name_fa')->label(__('admin.designs.singular'))
                    ->description(fn (Design $record): string => $record->collection->name_fa)
                    ->wrap(),
                TextColumn::make('updated_at')->label(__('admin.dashboard.last_updated'))
                    ->since()->dateTimeTooltip(),
                TextColumn::make('page_views')->label(__('admin.dashboard.total_page_views'))->default(0)->numeric(locale: 'fa'),
            ])
            ->recordUrl(fn (Design $record) => DesignResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading(__('admin.dashboard.no_designs'));
    }
}
