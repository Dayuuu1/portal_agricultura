<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Filament\Resources\Announcements\Widgets\AnnouncementActivityChart;
use App\Filament\Resources\Announcements\Widgets\AnnouncementDailyStats;
use App\Filament\Resources\Announcements\Widgets\AnnouncementStatsOverview;
use App\Models\Announcement;
use App\Support\SiteAnnouncements;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * What visitors did with one announcement: key figures, activity per day and the daily detail.
 */
class AnnouncementResults extends Page
{
    use InteractsWithRecord;

    protected static string $resource = AnnouncementResource::class;

    protected static ?string $navigationLabel = 'Resultados';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $breadcrumb = 'Resultados';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        AnnouncementResource::authorizeView($this->getRecord());
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function canAccess(array $parameters = []): bool
    {
        $record = $parameters['record'] ?? null;

        return $record instanceof Model
            ? AnnouncementResource::canView($record)
            : AnnouncementResource::canViewAny();
    }

    public function getTitle(): string
    {
        return 'Resultados del anuncio';
    }

    public function getSubheading(): string
    {
        /** @var Announcement $announcement */
        $announcement = $this->getRecord();

        return '«'.$announcement->title.'» · '.$announcement->visibility()['label']
            .($announcement->ends_at ? ' hasta el '.$announcement->ends_at->format('d/m/Y') : '')
            .' · '.$announcement->placement->getLabel().', '.mb_strtolower($announcement->frequency->getLabel()).'.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('previewOnSite')
                ->label('Probar en el portal')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (): string => route('home', [SiteAnnouncements::PREVIEW_PARAMETER => $this->getRecord()->getKey()]))
                ->openUrlInNewTab(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AnnouncementStatsOverview::class,
            AnnouncementActivityChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getFooterWidgets(): array
    {
        return [
            AnnouncementDailyStats::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }
}
