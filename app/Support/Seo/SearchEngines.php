<?php

namespace App\Support\Seo;

use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * What the portal tells search engines: the canonical address of each page, the ownership codes of Google Search
 * Console and Bing Webmaster Tools, and the robots.txt rules.
 */
final class SearchEngines
{
    /**
     * Filters that make a different page worth indexing on its own (a document type, a year, a category, page 2…).
     * Anything else (searches, campaign tags, previews) points back to the plain address.
     */
    public const CANONICAL_QUERY = ['seccion', 'tipo', 'categoria', 'estado', 'anio', 'vista', 'page'];

    /**
     * Addresses crawlers should leave alone: the panel, the internal search, and the endpoints the pages call.
     */
    public const DISALLOWED_PATHS = ['/admin/', '/livewire/', '/buscar', '/estadisticas/', '/anuncios/', '/mesa-de-partes/mi-cuenta'];

    /**
     * Only a production site is offered to search engines: a copy for tests or training must never be indexed.
     */
    public static function indexingAllowed(): bool
    {
        return app()->environment('production');
    }

    public static function canonicalUrl(Request $request): string
    {
        $query = collect($request->query())
            ->only(self::CANONICAL_QUERY)
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->reject(fn (string $value, string $key): bool => $key === 'page' && (int) $value <= 1)
            ->sortKeys()
            ->all();

        return $request->url().($query === [] ? '' : '?'.http_build_query($query));
    }

    /**
     * The code to put in the verification meta tag. Accepts the code alone or the whole tag as Google or Bing show
     * it (<meta name="google-site-verification" content="…">); anything else is ignored.
     */
    public static function verificationCode(string $settingKey): ?string
    {
        $value = trim((string) Setting::valueOf($settingKey));

        if (preg_match('/content\s*=\s*["\']?([A-Za-z0-9_\-]{10,100})/i', $value, $match) === 1) {
            return $match[1];
        }

        return preg_match('/^[A-Za-z0-9_\-]{10,100}$/', $value) === 1 ? $value : null;
    }

    public static function robotsTxt(): string
    {
        if (! self::indexingAllowed()) {
            return "# Copia de pruebas o desarrollo: no debe aparecer en los buscadores.\nUser-agent: *\nDisallow: /\n";
        }

        $lines = ['User-agent: *', 'Allow: /'];

        foreach (self::DISALLOWED_PATHS as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        return implode("\n", [...$lines, '', 'Sitemap: '.route('seo.sitemap')])."\n";
    }
}
