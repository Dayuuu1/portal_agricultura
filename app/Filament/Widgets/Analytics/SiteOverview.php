<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Concerns\ShowsSiteStatistics;
use App\Support\Analytics\SiteStatistics;
use App\Support\Analytics\StatisticsReport;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Key indicators of the period, each compared with the period of the same length right before it.
 */
class SiteOverview extends StatsOverviewWidget
{
    use ShowsSiteStatistics;

    private const ICONS = [
        'visitors' => Heroicon::OutlinedUsers,
        'visits' => Heroicon::OutlinedArrowRightEndOnRectangle,
        'views' => Heroicon::OutlinedEye,
        'visitDuration' => Heroicon::OutlinedClock,
        'pagesPerVisit' => Heroicon::OutlinedSquare2Stack,
        'bounceRate' => Heroicon::OutlinedArrowUturnLeft,
    ];

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Indicadores clave';

    protected function getDescription(): ?string
    {
        return $this->period()->label().' · comparado con los '.$this->period()->days().' días anteriores.';
    }

    protected function getColumns(): int|array
    {
        return ['md' => 3];
    }

    protected function getStats(): array
    {
        $report = new StatisticsReport($this->period());
        $trend = $report->trend();

        return array_map(function (array $indicator) use ($trend): Stat {
            $stat = Stat::make($indicator['label'], $indicator['value'])->icon(self::ICONS[$indicator['key']]);

            if (in_array($indicator['key'], ['visitors', 'views'], true)) {
                $stat->chart(array_column($trend, $indicator['key']));
            }

            if ($indicator['change'] === null) {
                return $stat->description('Sin datos del periodo anterior para comparar')->color('gray');
            }

            return $stat
                ->description(($indicator['change'] > 0 ? '+' : '').SiteStatistics::formatNumber($indicator['change'], 1).' % frente a '.$indicator['previousValue'])
                ->descriptionIcon($indicator['change'] >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->color(match ($indicator['improved']) {
                    true => 'success',
                    false => 'danger',
                    null => 'gray',
                });
        }, $report->indicators());
    }
}
