<?php

namespace App\Filament\Concerns;

use App\Models\User;
use App\Support\Analytics\AnalyticsPeriod;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * For the widgets of «Estadísticas del portal»: they read the period chosen in the page filters, and every
 * Livewire request checks again that the user may see the statistics.
 */
trait ShowsSiteStatistics
{
    use InteractsWithPageFilters;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasPermission('estadisticas.view');
    }

    protected function period(): AnalyticsPeriod
    {
        return AnalyticsPeriod::fromFilters($this->pageFilters);
    }
}
