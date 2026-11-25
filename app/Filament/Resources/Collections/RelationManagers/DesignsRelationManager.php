<?php

namespace App\Filament\Resources\Collections\RelationManagers;

use App\Filament\Resources\Designs\DesignResource;
use App\Models\Design;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DesignsRelationManager extends RelationManager
{
    protected static string $relationship = 'designs';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.designs.plural');
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name_fa')->label(__('admin.languages.fa'))->searchable(),
            TextColumn::make('name_en')->label(__('admin.languages.en'))->searchable(),
            TextColumn::make('published_at')->label(__('admin.designs.published_at'))->dateTime()->placeholder(__('admin.designs.draft')),
        ])->recordUrl(fn (Design $record) => DesignResource::getUrl('view', ['record' => $record]));
    }
}
