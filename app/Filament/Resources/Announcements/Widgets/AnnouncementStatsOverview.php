<?php

namespace App\Filament\Resources\Announcements\Widgets;

use App\Models\Announcement;
use App\Models\AnnouncementStat;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

/**
 * Key figures of one announcement, on its «Resultados» page.
 */
class AnnouncementStatsOverview extends StatsOverviewWidget
{
    public ?Model $record = null;

    /**
     * Checked again on every Livewire request: the record passed in is not re-authorized by Filament.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Announcement::class) ?? false;
    }

    protected ?string $heading = 'Resumen';

    protected ?string $description = 'Se cuentan veces, no personas: quien elige «En cada visita» suma una vista por visita. No se guarda ningún dato del visitante.';

    protected function getStats(): array
    {
        if (! $this->record instanceof Announcement) {
            return [];
        }

        $total = AnnouncementStat::totals($this->record);
        $lastWeek = AnnouncementStat::totals($this->record, 7);
        $trend = AnnouncementStat::series($this->record, 14);
        $clickRate = AnnouncementStat::rate($total['clicks'], $total['views']);

        return [
            Stat::make('Vistas', AnnouncementStat::formatCount($total['views']))
                ->description(AnnouncementStat::formatCount($lastWeek['views']).' en los últimos 7 días')
                ->descriptionIcon(Heroicon::OutlinedEye)
                ->chart(array_column($trend, 'views'))
                ->color('primary'),
            Stat::make('Clics en el botón', AnnouncementStat::formatCount($total['clicks']))
                ->description(AnnouncementStat::formatCount($lastWeek['clicks']).' en los últimos 7 días')
                ->descriptionIcon(Heroicon::OutlinedCursorArrowRays)
                ->chart(array_column($trend, 'clicks'))
                ->color('warning'),
            Stat::make('Tasa de clic', AnnouncementStat::formatRate($clickRate))
                ->description($clickRate === null
                    ? 'Aún no se ha mostrado a ningún visitante'
                    : 'De cada 100 vistas, '.AnnouncementStat::formatCount((int) round($clickRate)).' entraron')
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                ->color('success'),
            Stat::make('Cerrados sin entrar', AnnouncementStat::formatCount($total['dismissals']))
                ->description(AnnouncementStat::formatRate(AnnouncementStat::rate($total['dismissals'], $total['views'])).' de las vistas')
                ->descriptionIcon(Heroicon::OutlinedXCircle)
                ->color('gray'),
        ];
    }
}
