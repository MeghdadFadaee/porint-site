<?php

namespace App\Filament\Resources\GarmentTemplates\Pages;

use App\Filament\Resources\GarmentTemplates\GarmentTemplateResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditGarmentTemplate extends EditRecord
{
    protected static string $resource = GarmentTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['placements'] = GarmentTemplateResource::placementRows($data['placements']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return GarmentTemplateResource::prepareCalibration($data, $this->getRecord());
    }
}
