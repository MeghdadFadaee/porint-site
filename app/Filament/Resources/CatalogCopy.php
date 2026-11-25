<?php

namespace App\Filament\Resources;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

class CatalogCopy
{
    public static function fields(): array
    {
        return [
            self::fieldsFor('fa', 'rtl', 'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u'),
            self::fieldsFor('en', 'ltr', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),
        ];
    }

    private static function fieldsFor(string $locale, string $direction, string $slugRule): Section
    {
        $attributes = ['lang' => $locale, 'dir' => $direction];

        return Section::make(__("admin.languages.$locale"))->schema([
            TextInput::make("name_$locale")->label(__('admin.catalog.name'))->required()->maxLength(120)
                ->extraInputAttributes($attributes),
            TextInput::make("slug_$locale")->label(__('admin.catalog.slug'))->required()->maxLength(120)
                ->rule($slugRule)->unique(ignoreRecord: true)->extraInputAttributes($attributes),
            Textarea::make("description_$locale")->label(__('admin.catalog.description'))->required()->maxLength(1200)
                ->extraInputAttributes($attributes)->columnSpanFull(),
        ])->columns(2)->columnSpanFull();
    }

    public static function entries(): array
    {
        return array_map(fn (string $locale): Section => Section::make(__("admin.languages.$locale"))->schema([
            TextEntry::make("name_$locale")->label(__('admin.catalog.name')),
            TextEntry::make("slug_$locale")->label(__('admin.catalog.slug')),
            TextEntry::make("description_$locale")->label(__('admin.catalog.description'))->columnSpanFull(),
        ])->columns(2)->columnSpanFull(), ['fa', 'en']);
    }
}
