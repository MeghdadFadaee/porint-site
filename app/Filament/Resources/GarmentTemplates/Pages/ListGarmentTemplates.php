<?php

namespace App\Filament\Resources\GarmentTemplates\Pages;

use App\Filament\Resources\GarmentTemplates\GarmentTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGarmentTemplates extends ListRecords
{
    protected static string $resource = GarmentTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
