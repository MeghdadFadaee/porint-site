<?php

namespace App\Filament\Resources\GarmentTemplates\Pages;

use App\Filament\Resources\GarmentTemplates\GarmentTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGarmentTemplate extends CreateRecord
{
    protected static string $resource = GarmentTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return GarmentTemplateResource::prepareCalibration($data);
    }
}
