<?php

namespace App\Support\Documents;

use Illuminate\Support\Str;

/**
 * Reporting period and series name read from the title of a periodic publication.
 *
 * "Producción de los principales cultivos – julio 2025" reports on July 2025 and belongs to the series
 * "Producción de los principales cultivos". Titles without a single month ("… año 2019", campaign ranges
 * such as "agosto – julio / 2025 – 2026") keep their series name but have no period.
 */
final readonly class TitlePeriod
{
    private const MONTHS = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6, 'julio' => 7,
        'agosto' => 8, 'setiembre' => 9, 'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12,
    ];

    public function __construct(
        public string $seriesName,
        public ?int $year = null,
        public ?int $month = null,
        public ?int $day = null,
    ) {}

    public static function fromTitle(string $title, ?int $fallbackYear = null): self
    {
        $pattern = self::periodPattern();
        preg_match_all($pattern, $title, $matches, PREG_SET_ORDER);

        $seriesName = self::cleanSeriesName((string) preg_replace($pattern, ' ', $title)) ?: trim($title);
        $months = array_unique(array_map(fn (array $match): int => self::MONTHS[Str::lower($match['month'])], $matches));

        if (count($months) !== 1) {
            return new self($seriesName);
        }

        $match = collect($matches)->first(fn (array $candidate): bool => filled($candidate['year'] ?? null)) ?? $matches[0];
        $year = filled($match['year'] ?? null) ? (int) $match['year'] : (self::singleYear($title) ?? $fallbackYear);

        if ($year === null) {
            return new self($seriesName);
        }

        $day = (int) ($match['day'] ?? 0);

        return new self($seriesName, $year, reset($months), $day >= 1 && $day <= 31 ? $day : null);
    }

    public function hasMonth(): bool
    {
        return $this->month !== null;
    }

    /**
     * Accent- and case-insensitive key so "Boletin", "BOLETIN" and "Boletín" fall in the same series.
     */
    public function seriesKey(): string
    {
        return Str::of($this->seriesName)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->toString();
    }

    /**
     * Matches "julio 2025", "ENERO-2019", "JULIO2025", "julio de 2025" and "fecha 02 de abril".
     */
    private static function periodPattern(): string
    {
        $months = implode('|', array_keys(self::MONTHS));

        return '/(?:(?<!\p{L})fecha\s+)?(?:(?<!\d)(?<day>\d{1,2})\s*(?:de\s+)?)?(?<!\p{L})(?<month>'.$months.')(?!\p{L})'
            .'(?:\s*(?:del?\s+)?[-–—\/]?\s*(?<year>(?:19|20)\d{2})(?!\d))?/iu';
    }

    private static function singleYear(string $title): ?int
    {
        preg_match_all('/(?<![\p{L}\d])((?:19|20)\d{2})(?!\d)/u', $title, $matches);
        $years = array_unique($matches[1]);

        return count($years) === 1 ? (int) reset($years) : null;
    }

    private static function cleanSeriesName(string $text): string
    {
        $text = (string) preg_replace([
            '/\.(?:pdf|docx?|xlsx?|pptx?|zip|rar)(?!\p{L})/iu',
            '/\([^)]*\)/u',
            '/(?<![\p{L}\d-])(?:19|20)\d{2}(?![\p{L}\d-])/u',
            '/(?<!\p{L})(?:año|fecha|kgha|skg)(?!\p{L})/iu',
            '/\s+/u',
        ], ['', '', '', '', ' '], $text);

        do {
            $previous = $text;
            $text = (string) preg_replace([
                '/[\s\-–—_,:;\/.|]+$/u',
                '/^[\s\-–—_,:;\/.|]+/u',
                '/\s+(?:de|del|a|al|en|y)$/iu',
            ], '', $text);
        } while ($text !== $previous);

        return Str::ucfirst($text);
    }
}
