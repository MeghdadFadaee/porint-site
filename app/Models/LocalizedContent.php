<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class LocalizedContent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function text(string $field, ?string $locale = null): string
    {
        return $this->getAttribute($field.'_'.($locale ?? app()->getLocale()));
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('published_at', '<=', now());
    }
}
