<?php

namespace App\Filament\Resources\GarmentTemplates;

use App\Filament\Resources\GarmentTemplates\Pages\CreateGarmentTemplate;
use App\Filament\Resources\GarmentTemplates\Pages\EditGarmentTemplate;
use App\Filament\Resources\GarmentTemplates\Pages\ListGarmentTemplates;
use App\Filament\Resources\GarmentTemplates\Pages\ViewGarmentTemplate;
use App\Models\GarmentTemplate;
use App\Support\CatalogImage;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;

class GarmentTemplateResource extends Resource
{
    protected static ?string $model = GarmentTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name_fa';

    public static function getModelLabel(): string
    {
        return __('admin.garments.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.garments.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.garments.singular'))->schema([
                TextInput::make('name_fa')->label(__('admin.catalog.name_fa'))->required()->maxLength(120),
                TextInput::make('name_en')->label(__('admin.catalog.name_en'))->required()->maxLength(120)
                    ->extraInputAttributes(['lang' => 'en', 'dir' => 'ltr']),
                Select::make('garment')->label(__('admin.garments.garment'))->options(__('studio.garment_labels'))->required(),
                Select::make('color')->label(__('admin.garments.color'))->options(__('studio.color_labels'))->required(),
                Select::make('side')->label(__('admin.garments.side'))->options(__('admin.garments.sides'))->default('front')->live()->required()
                    ->rule(fn (Get $get, ?GarmentTemplate $record): Unique => Rule::unique('garment_templates', 'side')
                        ->where('garment', $get('garment'))->where('color', $get('color'))->ignore($record?->getKey()))
                    ->validationMessages(['unique' => __('admin.garments.duplicate_variant')]),
                Toggle::make('illustrative')->label(__('admin.catalog.illustrative'))->default(true),
                FileUpload::make('photo_upload')->label(__('admin.garments.photo'))->helperText(__('admin.garments.photo_hint'))
                    ->required(fn (?GarmentTemplate $record): bool => $record === null)->disk('local')->directory('garment-originals')
                    ->visibility('private')->preventFilePathTampering()->acceptedFileTypes(CatalogImage::MIME_TYPES)->maxSize(12288)
                    ->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
            Section::make(__('admin.garments.calibration'))->schema([
                TextInput::make('pixels_per_cm')->label(__('admin.garments.pixels_per_cm'))->helperText(__('admin.garments.calibration_hint'))
                    ->required()->numeric()->minValue(0.001)->maxValue(99999.999),
                Repeater::make('placements')->label(__('admin.garments.placements'))->required()->minItems(1)
                    ->addActionLabel(__('admin.garments.add_placement'))->schema([
                        Select::make('placement')->label(__('admin.garments.placement'))
                            ->options(fn (Get $get): array => self::placementOptions($get('../../side')))
                            ->distinct()->required()->columnSpanFull(),
                        TextInput::make('x')->label(__('admin.garments.x'))->required()->numeric()->minValue(0)->maxValue(100),
                        TextInput::make('y')->label(__('admin.garments.y'))->required()->numeric()->minValue(0)->maxValue(100),
                        TextInput::make('max_width_cm')->label(__('admin.garments.max_width'))->required()->numeric()->minValue(0.1)->maxValue(100),
                        TextInput::make('max_height_cm')->label(__('admin.garments.max_height'))->required()->numeric()->minValue(0.1)->maxValue(100),
                    ])->columns(2),
            ])->columnSpanFull(),
        ]);
    }

    private static function placementOptions(?string $side): array
    {
        $back = ['back', 'upper-back', 'lower-back'];
        $labels = __('studio.placement_labels');

        return $side === 'back' ? array_intersect_key($labels, array_flip($back)) : array_diff_key($labels, array_flip($back));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.garments.singular'))->schema([
                ImageEntry::make('photo')->label(__('admin.garments.photo'))->imageHeight(180)
                    ->state(fn (GarmentTemplate $record) => $record->photoUrl())->columnSpanFull(),
                TextEntry::make('name_fa')->label(__('admin.catalog.name_fa')),
                TextEntry::make('name_en')->label(__('admin.catalog.name_en')),
                TextEntry::make('garment')->label(__('admin.garments.garment'))->formatStateUsing(fn (string $state) => __("studio.garment_labels.$state")),
                TextEntry::make('color')->label(__('admin.garments.color'))->formatStateUsing(fn (string $state) => __("studio.color_labels.$state")),
                TextEntry::make('side')->label(__('admin.garments.side'))->formatStateUsing(fn (string $state) => __("admin.garments.sides.$state")),
                IconEntry::make('illustrative')->label(__('admin.catalog.illustrative'))->boolean(),
                TextEntry::make('photo_width_px')->label(__('admin.garments.photo_width')),
                TextEntry::make('pixels_per_cm')->label(__('admin.garments.pixels_per_cm')),
            ])->columns(2)->columnSpanFull(),
            RepeatableEntry::make('placements')->label(__('admin.garments.placements'))
                ->state(fn (GarmentTemplate $record) => self::placementRows($record->placements))
                ->schema([
                    TextEntry::make('placement')->label(__('admin.garments.placement'))
                        ->formatStateUsing(fn (string $state) => __("studio.placement_labels.$state")),
                    TextEntry::make('x')->label(__('admin.garments.x')),
                    TextEntry::make('y')->label(__('admin.garments.y')),
                    TextEntry::make('max_width_cm')->label(__('admin.garments.max_width')),
                    TextEntry::make('max_height_cm')->label(__('admin.garments.max_height')),
                ])->columns(5)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name_fa')->label(__('admin.catalog.name_fa'))->searchable(),
            TextColumn::make('side')->label(__('admin.garments.side'))->formatStateUsing(fn (string $state) => __("admin.garments.sides.$state")),
            TextColumn::make('pixels_per_cm')->label(__('admin.garments.pixels_per_cm')),
        ])->recordUrl(fn (GarmentTemplate $record) => self::getUrl('view', ['record' => $record]))
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()]);
    }

    public static function placementRows(array $placements): array
    {
        $rows = [];
        foreach ($placements as $name => $placement) {
            $rows[] = ['placement' => $name, ...$placement];
        }

        return $rows;
    }

    public static function prepareCalibration(array $data, ?GarmentTemplate $record = null): array
    {
        $photoWidth = $record?->photo_width_px;
        $upload = $data['photo_upload'] ?? null;
        if ($upload) {
            $photo = app(CatalogImage::class)->prepare($upload, GarmentTemplate::PHOTO_DIRECTORY, 'data.photo_upload', square: true);
            $data['photo'] = $photo['path'];
            $data['photo_width_px'] = $photoWidth = $photo['width'];
        }

        $placements = [];
        foreach ($data['placements'] as $placement) {
            $anchor = $placement['placement'];
            unset($placement['placement']);

            foreach (['x' => 'max_width_cm', 'y' => 'max_height_cm'] as $axis => $limit) {
                $availablePixels = 2 * min($placement[$axis], 100 - $placement[$axis]) * $photoWidth / 100;
                if ($availablePixels < $placement[$limit] * $data['pixels_per_cm']) {
                    throw ValidationException::withMessages(['data.placements' => __('admin.garments.placement_outside_photo')]);
                }
            }

            $placements[$anchor] = $placement;
        }

        $data['placements'] = $placements;
        unset($data['photo_upload']);

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGarmentTemplates::route('/'),
            'create' => CreateGarmentTemplate::route('/create'),
            'view' => ViewGarmentTemplate::route('/{record}'),
            'edit' => EditGarmentTemplate::route('/{record}/edit'),
        ];
    }
}
