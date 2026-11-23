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
use App\Support\OutgoingMail;
use App\Support\RichTextAttributeSanitizer;
use App\Support\SiteNavigation;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View as ViewInstance;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The mail server set up in «Correo y acceso» replaces the .env one; read only when an e-mail is about to go out.
        $this->app->afterResolving('mail.manager', function (): void {
            rescue(fn () => OutgoingMail::apply(), report: false);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->pinApplicationUrl();
        $this->configureRateLimiting();
        $this->hardenPanelInputs();

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

    /**
     * Outside local development every link (password resets, notices to the staff) is built from APP_URL,
     * never from the Host header of the request, which whoever sends the request can forge.
     */
    private function pinApplicationUrl(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        URL::forceRootUrl(config('app.url'));

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }

    /**
     * One counter per form and per visitor, so filling one form never blocks the others, plus a daily ceiling
     * against slow, steady abuse. Each limit needs its own key, or both would share one counter.
     */
    private function configureRateLimiting(): void
    {
        $perVisitor = fn (int $perMinute, int $perDay): Closure => fn (Request $request): array => [
            Limit::perMinute($perMinute)->by('minute|'.$request->ip()),
            Limit::perDay($perDay)->by('day|'.$request->ip()),
        ];

        RateLimiter::for('contact', $perVisitor(5, 20));
        RateLimiter::for('complaints', $perVisitor(5, 20));
        RateLimiter::for('mesa-partes-lookup', $perVisitor(10, 60));
        RateLimiter::for('mesa-partes-register', $perVisitor(5, 20));
        RateLimiter::for('mesa-partes-login', $perVisitor(10, 100));
        RateLimiter::for('mesa-partes-password', $perVisitor(5, 20));
    }

    /**
     * Safe defaults for every form and table of the panel, including the ones added later.
     */
    private function hardenPanelInputs(): void
    {
        // Columns edited right in the table skip the edit form and its policy check, so they ask the policy themselves.
        foreach ([ToggleColumn::class, CheckboxColumn::class, SelectColumn::class, TextInputColumn::class] as $columnClass) {
            $columnClass::configureUsing(fn (Column $column) => $column->disabled(
                fn (?Model $record): bool => $record === null || Gate::denies('update', $record),
            ));
        }

        // A stored file can only be kept, never swapped for another path typed into the request.
        FileUpload::configureUsing(fn (FileUpload $upload): FileUpload => $upload->preventFilePathTampering());

        $this->app->extend(
            HtmlSanitizerConfig::class,
            fn (HtmlSanitizerConfig $config): HtmlSanitizerConfig => $config->withAttributeSanitizer(new RichTextAttributeSanitizer),
        );
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
