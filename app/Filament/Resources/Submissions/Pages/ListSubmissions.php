<?php

namespace App\Filament\Resources\Submissions\Pages;

use App\Enums\SubmissionStatus;
use App\Filament\Resources\Submissions\SubmissionResource;
use App\Models\Submission;
use App\Support\MesaPartes\MesaPartesConfig;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListSubmissions extends ListRecords
{
    protected static string $resource = SubmissionResource::class;

    public function getSubheading(): string
    {
        return MesaPartesConfig::enabled()
            ? 'Documentos recibidos por la Mesa de Partes Virtual. Revise los nuevos: admítalos y derívelos, u obsérvelos si falta un requisito ('.MesaPartesConfig::cureDays().' días hábiles para subsanar).'
            : 'La Mesa de Partes Virtual está deshabilitada: no recibe documentos nuevos. Puede habilitarla en Mesa de partes › Configuración.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publicPage')
                ->label('Ver mesa de partes pública')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(route('mesa-partes.index'))
                ->openUrlInNewTab(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'por-revisar' => Tab::make('Por revisar')
                ->icon(Heroicon::OutlinedInboxArrowDown)
                ->modifyQueryUsing(fn (Builder $query) => $query->toReview())
                ->badge(Submission::query()->toReview()->count())
                ->badgeColor('warning'),
            'observados' => Tab::make('Observados')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SubmissionStatus::Observado))
                ->badge(Submission::query()->expiredObservations()->count() ?: null)
                ->badgeColor('danger')
                ->badgeTooltip('Con el plazo para subsanar vencido'),
            'en-tramite' => Tab::make('En trámite')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [SubmissionStatus::Derivado, SubmissionStatus::EnAtencion])),
            'vencidos' => Tab::make('Plazo vencido')
                ->modifyQueryUsing(fn (Builder $query) => $query->overdue())
                ->badge(Submission::query()->overdue()->count() ?: null)
                ->badgeColor('danger'),
            'concluidos' => Tab::make('Concluidos')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', SubmissionStatus::closed())),
            'todos' => Tab::make('Todos')->badge(Submission::query()->count()),
        ];
    }
}
