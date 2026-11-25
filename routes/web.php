<?php

use App\Http\Controllers\StudioController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('/studio/preview/{id}/{locale}', [StudioController::class, 'draft'])
        ->whereNumber('id')->whereIn('locale', ['fa', 'en'])->name('studio.preview');
    Route::get('/studio/preview/{id}/{file}/image', [StudioController::class, 'draftArt'])
        ->whereNumber('id')->name('studio.preview-art');
    Route::get('/studio/collection-covers/{id}/{file}', [StudioController::class, 'draftCollectionCover'])
        ->whereNumber('id')->name('studio.collection-cover');
});
