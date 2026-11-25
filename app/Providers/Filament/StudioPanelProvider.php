<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetStudioLocale;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class StudioPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('studio')
            ->path('studio')
            ->login()
            ->profile(isSimple: false)
            ->topNavigation()
            ->brandName(fn () => __('admin.brand'))
            ->brandLogo(fn () => view('filament.studio-logo'))
            ->brandLogoHeight('2.375rem')
            ->favicon(fn () => asset('porint/favicon.png'))
            ->font('Vazirmatn', fn () => asset('fonts/studio-font.css'), LocalFontProvider::class)
            ->assets([Css::make('porint-wordmark', asset('porint/wordmark.css'))])
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.dashboard-charts'), scopes: Dashboard::class)
            ->renderHook(PanelsRenderHook::SCRIPTS_BEFORE, fn () => view('filament.request-messages'))
            ->colors([
                'primary' => '#ad263f',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                SetStudioLocale::class.':fa',
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ], isPersistent: true);
    }
}
