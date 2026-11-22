<?php

namespace App\Support\MesaPartes;

use App\Support\BusinessDays;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The Mesa de Partes Virtual receives documents at any time, but terms run from the «fecha de presentación»:
 * the same day when the document arrives on a working day up to the closing time, otherwise the next working
 * day (Ley N° 31170).
 */
final class FilingClock
{
    public static function filedOn(CarbonInterface $submittedAt): Carbon
    {
        $moment = Carbon::parse($submittedAt);

        return self::isWithinHours($moment)
            ? $moment->copy()->startOfDay()
            : BusinessDays::after($moment, 1);
    }

    /**
     * Whether a document sent at this moment counts as filed the same day.
     */
    public static function isWithinHours(CarbonInterface $moment): bool
    {
        $closing = Carbon::parse($moment)->setTimeFromTimeString(MesaPartesConfig::closingTime());

        return BusinessDays::isWorkingDay($moment) && Carbon::parse($moment)->lessThanOrEqualTo($closing);
    }
}
