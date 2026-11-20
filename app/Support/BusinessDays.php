<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Working days ("días hábiles") in Peru: Monday to Friday except national holidays.
 * Non-working days decreed for the public sector each year are not included.
 */
final class BusinessDays
{
    /**
     * Fixed-date national holidays (month-day).
     */
    private const FIXED_HOLIDAYS = [
        '01-01', // Año Nuevo
        '05-01', // Día del Trabajo
        '06-07', // Batalla de Arica y Día de la Bandera
        '06-29', // San Pedro y San Pablo
        '07-23', // Día de la Fuerza Aérea del Perú
        '07-28', '07-29', // Fiestas Patrias
        '08-06', // Batalla de Junín
        '08-30', // Santa Rosa de Lima
        '10-08', // Combate de Angamos
        '11-01', // Todos los Santos
        '12-08', // Inmaculada Concepción
        '12-09', // Batalla de Ayacucho
        '12-25', // Navidad
    ];

    /**
     * The date that is $days working days after $from (counting starts the next day).
     */
    public static function after(CarbonInterface $from, int $days): Carbon
    {
        $date = Carbon::parse($from)->startOfDay();

        while ($days > 0) {
            $date = $date->addDay();

            if (self::isWorkingDay($date)) {
                $days--;
            }
        }

        return $date;
    }

    /**
     * Working days from $from (exclusive) to $to (inclusive); negative when $to is in the past.
     */
    public static function between(CarbonInterface $from, CarbonInterface $to): int
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();
        $sign = $end->greaterThanOrEqualTo($start) ? 1 : -1;
        [$start, $end] = $sign === 1 ? [$start, $end] : [$end, $start];

        $count = 0;

        for ($date = $start->copy()->addDay(); $date->lessThanOrEqualTo($end); $date->addDay()) {
            if (self::isWorkingDay($date)) {
                $count++;
            }
        }

        return $sign * $count;
    }

    public static function isWorkingDay(CarbonInterface $date): bool
    {
        return ! $date->isWeekend()
            && ! in_array($date->format('m-d'), self::FIXED_HOLIDAYS, true)
            && ! in_array($date->format('Y-m-d'), self::holyWeek($date->year), true);
    }

    /**
     * Holy Thursday and Good Friday (Gregorian Easter, anonymous algorithm).
     *
     * @return list<string>
     */
    private static function holyWeek(int $year): array
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;
        $easter = Carbon::create($year, $month, $day);

        return [$easter->copy()->subDays(3)->format('Y-m-d'), $easter->copy()->subDays(2)->format('Y-m-d')];
    }
}
