<?php

namespace App\Filament\Resources\Collections;

use App\Filament\Resources\CatalogCopy;
use App\Filament\Resources\Collections\Pages\CreateCollection;
use App\Filament\Resources\Collections\Pages\EditCollection;
use App\Filament\Resources\Collections\Pages\ListCollections;
use App\Filament\Resources\Collections\Pages\ViewCollection;
use App\Filament\Resources\Collections\RelationManagers\DesignsRelationManager;
use App\Models\Collection;
use App\Support\CatalogImage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\ForeignKeyConstraintViolationException;

class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name_fa';

    public static function getModelLabel(): string
    {
        return __('admin.collections.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.collections.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ...CatalogCopy::fields(),
            Section::make(__('admin.collections.cover'))->schema([
                FileUpload::make('cover_upload')->label(__('admin.collections.cover'))
                    ->disk('local')->directory('collection-originals')->visibility('private')->preventFilePathTampering()
                    ->acceptedFileTypes(CatalogImage::MIME_TYPES)->maxSize(12288)
                    ->helperText(fn (?Collection $record) => $record?->cover ? __('admin.collections.keep_cover') : null),
                Toggle::make('remove_cover')->label(__('admin.collections.remove_cover'))
                    ->visible(fn (?Collection $record): bool => filled($record?->cover)),
            ])->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.collections.singular'))->schema([
                ImageEntry::make('cover')->label(__('admin.collections.cover'))->imageHeight(180)
                    ->state(fn (Collection $record) => $record->coverUrl(preview: true)),
                TextEntry::make('published_at')->label(__('admin.designs.published_at'))->dateTime()->placeholder(__('admin.designs.draft')),
            ])->columns(2)->columnSpanFull()
                ->description(fn (Collection $record) => $record->designs()->exists() ? __('admin.collections.delete_blocked') : null),
            ...CatalogCopy::entries(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name_fa')->label(__('admin.languages.fa'))->searchable(),
            TextColumn::make('name_en')->label(__('admin.languages.en'))->searchable(),
            TextColumn::make('designs_count')->counts('designs')->label(__('admin.collections.design_count')),
            TextColumn::make('published_at')->label(__('admin.designs.published_at'))->dateTime()->placeholder(__('admin.designs.draft')),
        ])->recordUrl(fn (Collection $record) => self::getUrl('view', ['record' => $record]))->recordActions([
            ViewAction::make(),
            EditAction::make(),
            ActionGroup::make(self::managementActions()),
        ]);
    }

    public static function managementActions(): array
    {
        return [
            Action::make('publish')->label(__('admin.designs.publish'))->icon(Heroicon::OutlinedArrowUpTray)->color('success')
                ->requiresConfirmation()
                ->visible(fn (Collection $record) => ! $record->published_at)
                ->action(function (Collection $record): void {
                    $record->update(['published_at' => now()]);
                    Notification::make()->title(__('admin.collections.published'))->success()->send();
                }),
            Action::make('unpublish')->label(__('admin.designs.unpublish'))->icon(Heroicon::OutlinedArchiveBoxArrowDown)->color('warning')
                ->requiresConfirmation()->modalDescription(__('admin.collections.unpublish_description'))
                ->visible(fn (Collection $record) => (bool) $record->published_at)
                ->action(fn (Collection $record) => $record->update(['published_at' => null])),
            DeleteAction::make()
                ->authorize(fn (Collection $record): bool => self::canDelete($record))
                ->failureNotificationTitle(__('admin.collections.delete_blocked'))
                ->using(function (Collection $record): bool {
                    try {
                        return (bool) $record->delete();
                    } catch (ForeignKeyConstraintViolationException) {
                        return false;
                    }
                }),
        ];
    }

    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record) && ! $record->designs()->exists();
    }

    public static function prepareCover(array $data): array
    {
        if (filled($data['cover_upload'] ?? null)) {
            $data['cover'] = app(CatalogImage::class)->prepare($data['cover_upload'], Collection::COVER_DIRECTORY, 'data.cover_upload')['path'];
        } elseif ($data['remove_cover'] ?? false) {
            $data['cover'] = null;
        }

        unset($data['cover_upload'], $data['remove_cover']);

        return $data;
    }

    public static function getRelations(): array
    {
        return [DesignsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollections::route('/'),
            'create' => CreateCollection::route('/create'),
            'view' => ViewCollection::route('/{record}'),
            'edit' => EditCollection::route('/{record}/edit'),
        ];
    }
}
