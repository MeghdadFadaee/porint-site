<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Design extends LocalizedContent
{
    protected function casts(): array
    {
        return [...parent::casts(), 'illustrative' => 'boolean'];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function dailyViews(): HasMany
    {
        return $this->hasMany(DesignDailyView::class);
    }

    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->published()->whereHas('collection', fn (Builder $collection) => $collection->published())
            ->whereNotNull('artwork_path');
    }
}
