<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Counts records per calendar month in PHP so it works the same on SQLite and MySQL.
 */
final class MonthlyCounts
{
    /**
     * @param  Builder<Model>  $query
     * @return array<string, int> Keyed by "Y-m", oldest first, including empty months.
     */
    public static function for(Builder $query, string $dateColumn, int $months = 12): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);
        $series = [];

        for ($month = $start->copy(); $month->lessThanOrEqualTo(now()); $month->addMonth()) {
            $series[$month->format('Y-m')] = 0;
        }

        $query->where($dateColumn, '>=', $start)
            ->where($dateColumn, '<=', now())
            ->pluck($dateColumn)
            ->each(function ($date) use (&$series): void {
                $key = Carbon::parse($date)->format('Y-m');

                if (isset($series[$key])) {
                    $series[$key]++;
                }
            });

        return $series;
    }

    /**
     * @param  array<string, int>  $series
     * @return list<string>
     */
    public static function labels(array $series): array
    {
        return array_map(
            fn (string $month): string => ucfirst(Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y')),
            array_keys($series),
        );
    }
}
