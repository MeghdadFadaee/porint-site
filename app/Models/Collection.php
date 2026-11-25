<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends LocalizedContent
{
    public const COVER_DIRECTORY = 'collection-covers';

    public function designs(): HasMany
    {
        return $this->hasMany(Design::class);
    }

    public function coverUrl(bool $preview = false): ?string
    {
        if (! $this->cover) {
            return null;
        }

        if (! str_starts_with($this->cover, self::COVER_DIRECTORY.'/')) {
            return asset($this->cover);
        }

        return route($preview ? 'studio.collection-cover' : 'collection.cover', [
            'id' => $this->id,
            'file' => basename($this->cover),
        ]);
    }
}
