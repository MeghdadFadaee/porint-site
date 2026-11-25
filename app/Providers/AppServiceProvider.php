<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('studio.footer', function (ViewContract $view): void {
            $view->with('studioInformation', Setting::read(Setting::STUDIO_PUBLIC_INFORMATION, []));
        });
    }
}
