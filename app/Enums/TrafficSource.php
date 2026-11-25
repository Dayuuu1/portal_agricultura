<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Where a visit came from, worked out from the referring site (or the utm_source of a campaign link).
 */
enum TrafficSource: string implements HasLabel
{
    case Direct = 'direct';
    case SearchEngine = 'search';
    case Social = 'social';
    case Government = 'government';
    case Referral = 'referral';

    private const SEARCH_ENGINES = ['google', 'bing', 'yahoo', 'duckduckgo', 'ecosia', 'yandex', 'baidu', 'search.brave', 'qwant'];

    private const SOCIAL_NETWORKS = ['facebook', 'fb.me', 'instagram', 'whatsapp', 'wa.me', 't.co', 'twitter', 'x.com', 'linkedin', 'lnkd.in', 'tiktok', 'youtube', 'youtu.be', 'telegram', 't.me', 'threads'];

    public static function classify(?string $referrerHost, ?string $utmSource = null): self
    {
        $origin = strtolower((string) ($referrerHost ?: $utmSource));

        return match (true) {
            $origin === '' => self::Direct,
            str_ends_with($origin, '.gob.pe') || $origin === 'gob.pe' => self::Government,
            self::mentions($origin, self::SEARCH_ENGINES) => self::SearchEngine,
            self::mentions($origin, self::SOCIAL_NETWORKS) => self::Social,
            default => self::Referral,
        };
    }

    /**
     * @param  list<string>  $names
     */
    private static function mentions(string $origin, array $names): bool
    {
        foreach ($names as $name) {
            if ($origin === $name || str_contains('.'.$origin.'.', '.'.$name.'.') || str_starts_with($origin, $name)) {
                return true;
            }
        }

        return false;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Direct => 'Directo o enlace guardado',
            self::SearchEngine => 'Buscadores (Google, Bing…)',
            self::Social => 'Redes sociales y WhatsApp',
            self::Government => 'Sitios del Estado (gob.pe)',
            self::Referral => 'Otros sitios web',
        };
    }
}
