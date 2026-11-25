<?php

use App\Http\Controllers\StudioController;
use App\Http\Middleware\SetStudioLocale;
use Illuminate\Support\Facades\Route;

foreach ([
    'fa' => ['', 'نصب-و-مراقبت'],
    'en' => ['en', 'apply-and-care'],
] as $locale => [$prefix, $oldGuideSlug]) {
    Route::prefix($prefix)->name($locale.'.')->middleware(SetStudioLocale::class.':'.$locale)
        ->group(function () use ($prefix, $oldGuideSlug): void {
            Route::get('/', [StudioController::class, 'home'])->name('home');
            Route::get('/collections', [StudioController::class, 'catalog'])->name('catalog');
            Route::get('/collections/{slug}', [StudioController::class, 'collection'])->name('collection');
            Route::get('/designs/{slug}', [StudioController::class, 'design'])->name('design');
            Route::get('/guides', [StudioController::class, 'guides'])->name('guides');
            Route::permanentRedirect('/guides/'.$oldGuideSlug, '/'.ltrim($prefix.'/guides', '/'));
        });
}

Route::get('/artwork/{id}/{file}', [StudioController::class, 'artwork'])
    ->whereNumber('id')->name('artwork');
Route::get('/collection-covers/{id}/{file}', [StudioController::class, 'collectionCover'])
    ->whereNumber('id')->name('collection.cover');
Route::get('/garment-photos/{id}/{file}', [StudioController::class, 'garmentPhoto'])
    ->whereNumber('id')->name('garment.photo');
Route::get('/sitemap.xml', [StudioController::class, 'sitemap'])->name('sitemap');
