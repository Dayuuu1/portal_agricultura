<?php

namespace App\Support\Analytics;

use App\Enums\SiteEventType;
use App\Enums\SiteSection;
use App\Enums\TrafficSource;
use App\Models\PageView;
use App\Models\SiteEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Turns what resources/js/analytics.js reports into page views and events, without personal data:
 * - the visitor is a hash of IP and browser with a salt that changes every day and is then forgotten, so it cannot
 *   be traced back nor followed from one day to the next; the IP itself is never stored;
 * - pages of a citizen's private area, or with codes and tokens in the address, are kept only as their section;
 * - query strings are dropped (searches are kept apart, as terms).
 */
final class PageViewRecorder
{
    /**
     * Visits end after this long without opening another page, as in most analytics tools.
     */
    public const SESSION_TIMEOUT_MINUTES = 30;

    /**
     * Addresses that may identify a person (account pages, receipts, password and e-mail tokens).
     */
    private const PRIVATE_PATHS = [
        '/mesa-de-partes/mi-cuenta',
        '/mesa-de-partes/restablecer-contrasena',
        '/mesa-de-partes/verificar-correo',
        '/libro-de-reclamaciones/constancia',
    ];

    /**
     * Panel staff browsing the portal and robots are left out of the figures.
     */
    public static function shouldTrack(Request $request): bool
    {
        return $request->user() === null && ! UserAgent::from($request->userAgent())->isBot();
    }

    /**
     * @param  array{path: string, title?: ?string, referrer?: ?string, utm_source?: ?string, utm_campaign?: ?string, width?: ?int, search_term?: ?string, search_results?: ?int, search_scope?: ?string, not_found?: ?bool}  $data
     */
    public static function record(Request $request, array $data): PageView
    {
        $now = now();
        $agent = UserAgent::from($request->userAgent());
        $visitor = self::visitor($request);
        [$path, $isPrivate] = self::normalizePath($data['path']);

        $previous = PageView::query()
            ->where('visitor', $visitor)
            ->where('created_at', '>=', $now->copy()->subMinutes(self::SESSION_TIMEOUT_MINUTES))
            ->latest('created_at')
            ->latest('id')
            ->first();

        $isEntry = $previous === null;
        $referrerHost = $isEntry ? self::externalHost($data['referrer'] ?? null, $request) : null;
        $utmSource = $isEntry ? self::clean($data['utm_source'] ?? null, 60) : null;

        $pageView = PageView::query()->create([
            'visitor' => $visitor,
            'session' => $previous->session ?? Str::random(16),
            'is_entry' => $isEntry,
            'path' => $path,
            'title' => $isPrivate ? null : self::title($data['title'] ?? null),
            'section' => SiteSection::fromPath($path),
            'source' => $isEntry ? TrafficSource::classify($referrerHost, $utmSource) : null,
            'referrer_host' => $referrerHost,
            'utm_source' => $utmSource,
            'utm_campaign' => $isEntry ? self::clean($data['utm_campaign'] ?? null, 100) : null,
            'device' => $agent->device($data['width'] ?? null),
            'browser' => $agent->browser(),
            'os' => $agent->os(),
            'date' => $now->format('Y-m-d'),
            'hour' => (int) $now->format('G'),
            'created_at' => $now,
        ]);

        if (filled($data['search_term'] ?? null)) {
            self::event($pageView, SiteEventType::Search, self::searchTerm($data['search_term']), $data['search_scope'] ?? null, (int) ($data['search_results'] ?? 0));
        }

        if (($data['not_found'] ?? false) && ! $isPrivate) {
            self::event($pageView, SiteEventType::NotFound, $path);
        }

        return $pageView;
    }

    /**
     * Time the page stayed visible. Reported more than once (each time the tab is hidden): only the longest counts.
     */
    public static function updateDuration(PageView $pageView, int $seconds): void
    {
        $seconds = min(max($seconds, 0), PageView::MAX_DURATION);

        PageView::query()
            ->whereKey($pageView->getKey())
            ->where(fn ($query) => $query->whereNull('duration')->orWhere('duration', '<', $seconds))
            ->update(['duration' => $seconds]);
    }

    /**
     * A click on «Descargar»: kept as the document's slug, the reports show its title.
     */
    public static function recordDownload(PageView $pageView, string $target): void
    {
        if (preg_match('#^/documentos/([a-z0-9-]{1,140})/descargar/?$#', $target, $match) === 1) {
            self::event($pageView, SiteEventType::Download, $match[1]);
        }
    }

    /**
     * A click on a link to another site: only its domain is kept.
     */
    public static function recordOutbound(PageView $pageView, string $target): void
    {
        $host = self::host($target);

        if ($host !== null && $host !== self::host((string) config('app.url'))) {
            self::event($pageView, SiteEventType::Outbound, $host);
        }
    }

    private static function event(PageView $pageView, SiteEventType $type, string $label, ?string $context = null, ?int $value = null): void
    {
        SiteEvent::query()->create([
            'page_view_id' => $pageView->getKey(),
            'type' => $type,
            'label' => Str::limit($label, 150, ''),
            'context' => $context,
            'value' => $value,
            'date' => $pageView->date,
            'created_at' => now(),
        ]);
    }

    /**
     * The salt of the day lives only in the cache and expires: yesterday's hashes cannot be recomputed.
     */
    private static function visitor(Request $request): string
    {
        $salt = Cache::remember('analytics:salt:'.today()->format('Y-m-d'), now()->addHours(36), fn (): string => Str::random(40));

        return substr(hash('sha256', $salt.'|'.$request->ip().'|'.$request->userAgent()), 0, 16);
    }

    /**
     * @return array{0: string, 1: bool} The path to keep and whether it was a private one.
     */
    private static function normalizePath(string $path): array
    {
        $path = '/'.trim((string) Str::of($path)->before('?')->before('#')->lower(), '/');

        foreach (self::PRIVATE_PATHS as $private) {
            if ($path === $private || str_starts_with($path, $private.'/')) {
                return [$private, true];
            }
        }

        return [Str::limit($path, 255, ''), false];
    }

    /**
     * The page title without the portal name the layout appends («Noticias | Dirección Regional…»).
     */
    private static function title(?string $title): ?string
    {
        $title = trim(Str::beforeLast((string) $title, ' | '));

        return $title === '' ? null : Str::limit($title, 255, '');
    }

    private static function searchTerm(string $term): string
    {
        return Str::limit((string) Str::of($term)->squish()->lower(), 100, '');
    }

    /**
     * Domain of the site the visitor came from, or null when it is the portal itself or there is none.
     */
    private static function externalHost(?string $referrer, Request $request): ?string
    {
        $host = self::host($referrer);

        return $host === null || in_array($host, [self::host($request->getSchemeAndHttpHost()), self::host((string) config('app.url'))], true)
            ? null
            : Str::limit($host, 120, '');
    }

    private static function host(?string $url): ?string
    {
        $host = parse_url((string) $url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? Str::of($host)->lower()->chopStart('www.')->toString() : null;
    }

    private static function clean(?string $value, int $length): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : Str::limit(strip_tags($value), $length, '');
    }
}
