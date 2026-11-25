<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Concerns\ShowsSiteStatistics;
use App\Support\Analytics\SiteStatistics;
use Filament\Widgets\Widget;

/**
 * Who is on the portal right now, refreshed every 30 seconds. It ignores the period filter on purpose.
 */
class LiveVisitors extends Widget
{
    use ShowsSiteStatistics;

    public const MINUTES = 5;

    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected string $view = 'filament.analytics.live-visitors';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [...SiteStatistics::live(self::MINUTES), 'minutes' => self::MINUTES];
    }
}
