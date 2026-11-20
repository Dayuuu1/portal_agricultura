<?php

namespace App\Filament\Widgets;

use App\Enums\JobPostingStatus;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\JobPostings\JobPostingResource;
use App\Filament\Resources\Posts\PostResource;
use App\Models\ContactMessage;
use App\Models\Document;
use App\Models\JobPosting;
use App\Models\Post;
use App\Support\MonthlyCounts;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PortalStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Resumen del portal';

    protected ?string $description = 'Cifras de lo que hoy ve la ciudadanía. Haga clic en un indicador para ir a su sección.';

    protected function getStats(): array
    {
        $user = auth()->user();
        $unread = ContactMessage::query()->unread()->count();
        $postsByMonth = MonthlyCounts::for(Post::query()->published(), 'published_at', 6);
        $documentsByMonth = MonthlyCounts::for(Document::query()->published(), 'issued_at', 6);

        return [
            Stat::make('Noticias publicadas', number_format(Post::query()->published()->count()))
                ->description(end($postsByMonth).' este mes · '.array_sum($postsByMonth).' en 6 meses')
                ->descriptionIcon(Heroicon::OutlinedNewspaper)
                ->chart(array_values($postsByMonth))
                ->color('primary')
                ->url($user?->hasPermission('noticias.view') ? PostResource::getUrl() : null),
            Stat::make('Documentos publicados', number_format(Document::query()->published()->count()))
                ->description(number_format((int) Document::query()->sum('downloads')).' descargas acumuladas')
                ->descriptionIcon(Heroicon::OutlinedArrowDownTray)
                ->chart(array_values($documentsByMonth))
                ->color('info')
                ->url($user?->hasPermission('documentos.view') ? DocumentResource::getUrl() : null),
            Stat::make('Convocatorias en curso', JobPosting::query()->where('status', JobPostingStatus::EnCurso)->count())
                ->description(JobPosting::query()->count().' convocatorias en el historial')
                ->descriptionIcon(Heroicon::OutlinedBriefcase)
                ->color('success')
                ->url($user?->hasPermission('convocatorias.view') ? JobPostingResource::getUrl() : null),
            Stat::make('Mensajes sin leer', $unread)
                ->description($unread > 0 ? 'Requieren respuesta' : 'Todo al día')
                ->descriptionIcon($unread > 0 ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedCheckCircle)
                ->color($unread > 0 ? 'warning' : 'success')
                ->url($user?->hasPermission('mensajes.view') ? ContactMessageResource::getUrl() : null),
        ];
    }
}
