<?php

namespace App\Providers\Filament;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Institutional green, with #1c7c45 as the base tone so buttons keep white text.
     */
    private const BRAND_PALETTE = [
        50 => '#effaf2', 100 => '#d8f3df', 200 => '#b4e6c3', 300 => '#82d29f', 400 => '#2a9a58',
        500 => '#1c7c45', 600 => '#17633a', 700 => '#154f31', 800 => '#12412a', 900 => '#0d3220', 950 => '#082417',
    ];

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable()
                    ->brandName('DRA Madre de Dios'),
            ])
            ->brandName('DRA Madre de Dios · Administración')
            ->brandLogo(fn (): View => view('filament.brand'))
            ->brandLogoHeight('2.75rem')
            ->favicon('/favicon-32.png')
            ->defaultThemeMode(ThemeMode::Light)
            ->colors([
                'primary' => array_map(fn (string $hex): string => Color::convertToOklch($hex), self::BRAND_PALETTE),
                'warning' => Color::Amber,
                'gray' => Color::Slate,
            ])
            ->font('Inter')
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->unsavedChangesAlerts()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->navigationGroups([
                'Contenido',
                'Documentos',
                'Convocatorias',
                'Institucional',
                'Atención al ciudadano',
                'Configuración',
            ])
            ->userMenuItems([
                Action::make('publicSite')
                    ->label('Ver portal público')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->url(fn (): string => route('home'))
                    ->openUrlInNewTab(),
                Action::make('myActivity')
                    ->label('Mi actividad')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->url(fn (): string => ActivityLogResource::getUrl(parameters: ['filters' => ['user' => ['value' => auth()->id()]]]))
                    ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->hasPermission('actividad.view')),
            ])
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn (): View => view('filament.hooks.login-help'))
            ->renderHook(PanelsRenderHook::FOOTER, fn (): View => view('filament.hooks.footer'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
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
            ]);
    }
}
