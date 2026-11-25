<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GarmentTemplate extends Model
{
    public const PHOTO_DIRECTORY = 'garment-photos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'placements' => 'array',
            'illustrative' => 'boolean',
        ];
    }

    public function photoUrl(): string
    {
        if (! str_starts_with($this->photo, self::PHOTO_DIRECTORY.'/')) {
            return asset($this->photo);
        }

        return route('garment.photo', ['id' => $this->id, 'file' => basename($this->photo)]);
    }
}
