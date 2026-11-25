<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Concerns\ShowsSiteStatistics;
use App\Support\Analytics\SiteStatistics;
use Filament\Widgets\ChartWidget;

/**
 * Share of visitors on phones, tablets and computers.
 */
class Devices extends ChartWidget
{
    use ShowsSiteStatistics;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Dispositivos';

    protected ?string $description = 'Con qué equipo entran los visitantes. Si la mayoría usa celular, el contenido debe leerse bien en pantallas pequeñas.';

    protected ?string $maxHeight = '260px';

    /**
     * @return array<string, int>
     */
    private function devices(): array
    {
        return SiteStatistics::technology($this->period(), 'device');
    }

    protected function getData(): array
    {
        $devices = $this->devices();
        $total = max(array_sum($devices), 1);

        return [
            'datasets' => [[
                'label' => 'Visitantes',
                'data' => array_values($devices),
                'backgroundColor' => ['#1c7c45', '#ebb43a', '#94a3b8'],
                'borderWidth' => 0,
            ]],
            'labels' => array_map(
                fn (string $device, int $visitors): string => $device.' ('.SiteStatistics::formatNumber($visitors / $total * 100).' %)',
                array_keys($devices),
                $devices,
            ),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '62%',
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
        ];
    }

    public function getChartAssistiveContent(): string
    {
        return collect($this->devices())
            ->map(fn (int $visitors, string $device): string => $device.': '.SiteStatistics::formatNumber($visitors).' visitantes')
            ->implode('; ') ?: 'Sin visitantes en el periodo elegido.';
    }
}
