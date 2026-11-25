<?php

namespace App\Filament\Resources\Designs\Pages;

use App\Filament\Resources\Designs\DesignResource;
use App\Models\Collection;
use App\Models\Design;
use App\Support\Artwork;
use App\Support\GarmentPreview;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditDesign extends EditRecord
{
    protected static string $resource = DesignResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()];
    }

    public function getSubheading(): ?string
    {
        return $this->getRecord()->published_at ? __('admin.designs.live_edit') : null;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($record->published_at && (int) $data['collection_id'] !== (int) $record->collection_id
            && ! Collection::published()->whereKey($data['collection_id'])->exists()) {
            throw ValidationException::withMessages(['data.collection_id' => __('admin.designs.choose_published_collection')]);
        }

        if ($record->published_at
            && ((float) $data['width_cm'] !== (float) $record->width_cm || (float) $data['height_cm'] !== (float) $record->height_cm)
            && app(GarmentPreview::class)->options(new Design($data)) === []) {
            throw ValidationException::withMessages(['data.width_cm' => __('admin.designs.no_compatible_placement')]);
        }

        if ($data['original_path'] !== $record->original_path) {
            $data['artwork_path'] = app(Artwork::class)->prepare($data['original_path']);
        }

        return parent::handleRecordUpdate($record, $data);
    }
}
