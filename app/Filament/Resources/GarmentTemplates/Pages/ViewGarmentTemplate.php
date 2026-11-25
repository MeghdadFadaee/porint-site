<?php

namespace App\Filament\Resources\GarmentTemplates\Pages;

use App\Filament\Resources\GarmentTemplates\GarmentTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGarmentTemplate extends ViewRecord
{
    protected static string $resource = GarmentTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
