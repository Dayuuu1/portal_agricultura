<?php

namespace App\Filament\Resources\Announcements\Widgets;

use App\Models\Announcement;
use App\Models\AnnouncementStat;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Views and clicks of one announcement per day, on its «Resultados» page.
 */
class AnnouncementActivityChart extends ChartWidget
{
    public ?Model $record = null;

    /**
     * Checked again on every Livewire request: the record passed in is not re-authorized by Filament.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Announcement::class) ?? false;
    }

    public ?string $filter = '30';

    protected ?string $heading = 'Actividad por día';

    protected ?string $description = 'Vistas y clics de cada día. Pulse una serie de la leyenda para mostrarla u ocultarla.';

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 'full';

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Últimos 7 días',
            '30' => 'Últimos 30 días',
            '90' => 'Últimos 90 días',
        ];
    }

    /**
     * The filter is user-controllable: only the offered periods are accepted.
     */
    private function days(): int
    {
        return match ($this->filter) {
            '7' => 7,
            '90' => 90,
            default => 30,
        };
    }

    /**
     * @return array<string, array{views: int, clicks: int, dismissals: int}>
     */
    private function series(): array
    {
        return $this->record instanceof Announcement ? AnnouncementStat::series($this->record, $this->days()) : [];
    }

    protected function getData(): array
    {
        $series = $this->series();

        return [
            'datasets' => [
                [
                    'label' => 'Vistas',
                    'data' => array_column($series, 'views'),
                    'borderColor' => '#1c7c45',
                    'backgroundColor' => 'rgb(28 124 69 / 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Clics en el botón',
                    'data' => array_column($series, 'clicks'),
                    'borderColor' => '#d9961f',
                    'backgroundColor' => '#ebb43a',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Cerrados sin entrar',
                    'data' => array_column($series, 'dismissals'),
                    'borderColor' => '#94a3b8',
                    'backgroundColor' => '#cbd5e1',
                    'borderDash' => [4, 4],
                    'tension' => 0.3,
                    'hidden' => true,
                ],
            ],
            'labels' => array_map(fn (string $day): string => Carbon::parse($day)->translatedFormat('d M'), array_keys($series)),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }

    /**
     * A canvas says nothing to a screen reader: the same figures, summed up in words.
     */
    public function getChartAssistiveContent(): string
    {
        $series = $this->series();
        $dailyViews = array_column($series, 'views');

        if (array_sum($dailyViews) === 0) {
            return 'Sin vistas en el periodo elegido.';
        }

        $busiestDate = array_keys($series)[array_search(max($dailyViews), $dailyViews, true)];

        return sprintf(
            'En los últimos %d días: %s vistas y %s clics. El día con más vistas fue el %s, con %s.',
            $this->days(),
            AnnouncementStat::formatCount(array_sum($dailyViews)),
            AnnouncementStat::formatCount(array_sum(array_column($series, 'clicks'))),
            Carbon::parse($busiestDate)->translatedFormat('d \d\e F'),
            AnnouncementStat::formatCount($series[$busiestDate]['views']),
        );
    }
}
