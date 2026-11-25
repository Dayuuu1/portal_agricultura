<?php

namespace App\Support\Analytics;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Range of days a statistics report covers, chosen in the filters of «Estadísticas del portal», and the range of the
 * same length right before it, to compare with.
 */
final class AnalyticsPeriod
{
    public const OPTIONS = [
        'today' => 'Hoy',
        'yesterday' => 'Ayer',
        '7' => 'Últimos 7 días',
        '30' => 'Últimos 30 días',
        '90' => 'Últimos 90 días',
        '365' => 'Últimos 12 meses',
        'month' => 'Este mes',
        'custom' => 'Rango personalizado',
    ];

    public const DEFAULT = '30';

    public function __construct(public readonly CarbonImmutable $start, public readonly CarbonImmutable $end) {}

    /**
     * Filter values are user-controllable: anything unexpected falls back to the last 30 days.
     *
     * @param  array<string, mixed>|null  $filters
     */
    public static function fromFilters(?array $filters): self
    {
        $today = CarbonImmutable::today();

        return match ((string) ($filters['period'] ?? self::DEFAULT)) {
            'today' => new self($today, $today),
            'yesterday' => new self($today->subDay(), $today->subDay()),
            '7' => self::lastDays(7),
            '90' => self::lastDays(90),
            '365' => self::lastDays(365),
            'month' => new self($today->startOfMonth(), $today),
            'custom' => self::custom($filters['start'] ?? null, $filters['end'] ?? null) ?? self::lastDays(30),
            default => self::lastDays(30),
        };
    }

    public static function lastDays(int $days): self
    {
        $today = CarbonImmutable::today();

        return new self($today->subDays($days - 1), $today);
    }

    private static function custom(mixed $start, mixed $end): ?self
    {
        try {
            $from = CarbonImmutable::parse((string) $start)->startOfDay();
            $to = CarbonImmutable::parse((string) $end)->startOfDay();
        } catch (Throwable) {
            return null;
        }

        if (blank($start) || blank($end) || $from->greaterThan($to)) {
            return null;
        }

        // At most two years, so a mistyped date cannot ask for decades of rows.
        return new self(max($from, $to->subYears(2)), min($to, CarbonImmutable::today()));
    }

    public function days(): int
    {
        return (int) $this->start->diffInDays($this->end) + 1;
    }

    public function isSingleDay(): bool
    {
        return $this->start->equalTo($this->end);
    }

    public function previous(): self
    {
        return new self($this->start->subDays($this->days()), $this->start->subDay());
    }

    public function from(): string
    {
        return $this->start->format('Y-m-d');
    }

    public function to(): string
    {
        return $this->end->format('Y-m-d');
    }

    /**
     * @return list<string> Every day of the range, "Y-m-d", oldest first.
     */
    public function dates(): array
    {
        $dates = [];

        for ($day = $this->start; $day->lessThanOrEqualTo($this->end); $day = $day->addDay()) {
            $dates[] = $day->format('Y-m-d');
        }

        return $dates;
    }

    public function label(): string
    {
        return $this->isSingleDay()
            ? $this->start->translatedFormat('l j \d\e F \d\e Y')
            : 'Del '.$this->start->format('d/m/Y').' al '.$this->end->format('d/m/Y');
    }
}
