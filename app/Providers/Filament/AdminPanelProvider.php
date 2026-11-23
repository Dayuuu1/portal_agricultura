<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Http\Middleware\RequireMultiFactorAuthenticationByPolicy;
use App\Models\Setting;
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
            // Same answer for any address, so the form does not reveal who has a panel account.
            ->passwordReset(RequestPasswordReset::class)
            ->profile(isSimple: false)
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable()
                    // Name shown in the authenticator app; read while the panel is built, when the settings table may not exist yet (fresh installs).
                    ->brandName(rescue(fn (): string => Setting::shortName(), 'Dirección Regional Agraria', report: false)),
            ], isRequired: true)
            // «Required» only registers the setup step; who must go through it is chosen in «Correo y acceso».
            ->multiFactorAuthenticationRequiredMiddlewareName(RequireMultiFactorAuthenticationByPolicy::class)
            // Name and images come from «Datos del portal», so the entity can change them without touching the code.
            ->brandName(fn (): string => Setting::shortName().' · Administración')
            ->brandLogo(fn (): View => view('filament.brand'))
            ->brandLogoHeight('2.75rem')
            ->favicon(fn (): string => Setting::faviconUrl())
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
                'Mesa de partes',
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
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn (): View => view('filament.hooks.sidebar-footer'))
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
            // Persistent: every Livewire request is checked too, so a deactivated account stops working at its next click.
            ->authMiddleware([
                Authenticate::class,
            ], isPersistent: true);
    }
}
