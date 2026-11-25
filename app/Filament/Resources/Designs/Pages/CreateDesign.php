<?php

namespace App\Filament\Resources\Designs\Pages;

use App\Filament\Resources\Designs\DesignResource;
use App\Support\Artwork;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDesign extends CreateRecord
{
    protected static string $resource = DesignResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['artwork_path'] = app(Artwork::class)->prepare($data['original_path']);

        return parent::handleRecordCreation($data);
    }
}
