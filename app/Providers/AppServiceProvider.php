<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\DocumentType;
use App\Models\Link;
use App\Models\NonWorkingDay;
use App\Models\OrganizationalUnit;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Support\BusinessDays;
use App\Support\SiteNavigation;
use Filament\Tables\Table;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View as ViewInstance;

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
        View::composer(
            ['components.layouts.app', 'components.site.*', 'home', 'institutional.*', 'contact.*', 'complaints.*', 'transparency.*', 'agencies.*', 'mesa-partes.*', 'components.mesa-partes.*'],
            fn (ViewInstance $view) => $view->with('settings', Setting::allCached()),
        );

        View::composer('components.site.header', fn (ViewInstance $view) => $view->with('menu', SiteNavigation::data()));

        foreach ([OrganizationalUnit::class, Agency::class, DocumentType::class, Page::class] as $model) {
            $model::saved(fn () => SiteNavigation::flush());
            $model::deleted(fn () => SiteNavigation::flush());
        }

        View::composer('components.site.footer', fn (ViewInstance $view) => $view->with(
            'footerLinks',
            Link::query()->active()->orderBy('sort_order')->get()->groupBy(fn (Link $link): string => $link->group->value),
        ));

        $this->registerAuthenticationAudit();

        // Non-working days decreed for the public sector, registered in the panel, also stop the terms.
        BusinessDays::resolveExtraNonWorkingDaysUsing(fn (): array => rescue(fn (): array => NonWorkingDay::dates(), [], report: false));

        Password::defaults(fn (): Password => Password::min(10)->letters()->mixedCase()->numbers());

        Table::configureUsing(fn (Table $table): Table => $table
            ->striped()
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('No hay registros para mostrar')
            ->emptyStateDescription('Pruebe con otra pestaña, quite los filtros o cree un registro nuevo.'));
    }

    private function registerAuthenticationAudit(): void
    {
        Event::listen(function (Login $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            ActivityLog::record('login', $event->user, label: $event->user->email, userId: $event->user->id);
        });

        Event::listen(function (Logout $event): void {
            if ($event->user instanceof User) {
                ActivityLog::record('logout', $event->user, label: $event->user->email, userId: $event->user->id);
            }
        });

        Event::listen(function (Failed $event): void {
            // Only the panel's sign-in is audited here; citizens of the Mesa de Partes use their own guard.
            if ($event->guard !== 'web') {
                return;
            }

            $email = Str::limit((string) ($event->credentials['email'] ?? ''), 150);

            ActivityLog::record('login_failed', $event->user, label: $email, userId: $event->user?->id);
        });
    }
}
