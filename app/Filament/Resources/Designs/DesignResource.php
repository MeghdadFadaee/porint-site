<?php

namespace App\Filament\Resources\Designs;

use App\Filament\Resources\CatalogCopy;
use App\Filament\Resources\Designs\Pages\CreateDesign;
use App\Filament\Resources\Designs\Pages\EditDesign;
use App\Filament\Resources\Designs\Pages\ListDesigns;
use App\Filament\Resources\Designs\Pages\ViewDesign;
use App\Models\Design;
use App\Support\GarmentPreview;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class DesignResource extends Resource
{
    protected static ?string $model = Design::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name_fa';

    public static function getModelLabel(): string
    {
        return __('admin.designs.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.designs.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ...CatalogCopy::fields(),
            Section::make(__('admin.designs.print'))->schema([
                Select::make('collection_id')->label(__('admin.designs.collection'))->relationship('collection', 'name_fa')->required(),
                TextInput::make('width_cm')->label(__('admin.designs.width'))->required()->numeric()->minValue(0.1)->maxValue(100),
                TextInput::make('height_cm')->label(__('admin.designs.height'))->required()->numeric()->minValue(0.1)->maxValue(100),
                FileUpload::make('original_path')->label(__('admin.designs.artwork'))->required()
                    ->disk('local')->directory('originals')->visibility('private')->preventFilePathTampering()
                    ->acceptedFileTypes(['image/png'])->maxSize(12288)->columnSpanFull(),
                Toggle::make('illustrative')->label(__('admin.catalog.illustrative')),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.designs.print'))->schema([
                ImageEntry::make('artwork_path')->label(__('admin.designs.artwork'))->imageHeight(180)
                    ->state(fn (Design $record) => $record->artwork_path
                        ? route('studio.preview-art', ['id' => $record->id, 'file' => basename($record->artwork_path)]) : null)
                    ->alt(fn (Design $record) => $record->name_fa)
                    ->url(fn (?string $state) => $state, shouldOpenInNewTab: true)
                    ->columnSpanFull(),
                TextEntry::make('collection.name_fa')->label(__('admin.designs.collection')),
                TextEntry::make('published_at')->label(__('admin.designs.published_at'))->dateTime()->placeholder(__('admin.designs.draft')),
                TextEntry::make('width_cm')->label(__('admin.designs.width')),
                TextEntry::make('height_cm')->label(__('admin.designs.height')),
                TextEntry::make('page_views')->label(__('admin.dashboard.total_page_views'))
                    ->state(fn (Design $record) => $record->dailyViews()->sum('views'))->numeric(locale: 'fa'),
                IconEntry::make('illustrative')->label(__('admin.catalog.illustrative'))->boolean(),
            ])->columns(2)->columnSpanFull(),
            ...CatalogCopy::entries(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query) => $query->withSum('dailyViews as page_views', 'views'))->columns([
            TextColumn::make('name_fa')->label(__('admin.languages.fa'))->searchable(),
            TextColumn::make('name_en')->label(__('admin.languages.en'))->searchable(),
            TextColumn::make('collection.name_fa')->label(__('admin.designs.collection')),
            TextColumn::make('published_at')->label(__('admin.designs.published_at'))->dateTime()->placeholder(__('admin.designs.draft')),
            TextColumn::make('page_views')->label(__('admin.dashboard.total_page_views'))->default(0)->numeric(locale: 'fa')->sortable(),
        ])->recordUrl(fn (Design $record) => self::getUrl('view', ['record' => $record]))->recordActions([
            ViewAction::make(),
            EditAction::make(),
            ActionGroup::make(self::managementActions()),
        ]);
    }

    public static function managementActions(): array
    {
        return [
            Action::make('preview')->label(__('admin.designs.preview'))->icon(Heroicon::OutlinedPhoto)
                ->url(fn (Design $record) => route('studio.preview', ['id' => $record->id, 'locale' => 'fa']))->openUrlInNewTab(),
            Action::make('publish')->label(__('admin.designs.publish'))->icon(Heroicon::OutlinedArrowUpTray)->color('success')->requiresConfirmation()
                ->visible(fn (Design $record) => ! $record->published_at)
                ->action(function (Design $record): void {
                    if (! $record->artwork_path || ! Storage::disk('local')->exists($record->artwork_path)
                        || $record->collection->published_at?->lte(now()) !== true || app(GarmentPreview::class)->options($record) === []) {
                        Notification::make()->title(__('admin.designs.publish_requirements'))->danger()->send();

                        return;
                    }

                    $record->update(['published_at' => now()]);
                    Notification::make()->title(__('admin.designs.published'))->success()->send();
                }),
            Action::make('unpublish')->label(__('admin.designs.unpublish'))->icon(Heroicon::OutlinedArchiveBoxArrowDown)->requiresConfirmation()->color('warning')
                ->visible(fn (Design $record) => (bool) $record->published_at)
                ->action(fn (Design $record) => $record->update(['published_at' => null])),
            DeleteAction::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDesigns::route('/'),
            'create' => CreateDesign::route('/create'),
            'view' => ViewDesign::route('/{record}'),
            'edit' => EditDesign::route('/{record}/edit'),
        ];
    }
}
