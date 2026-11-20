<?php

namespace App\Filament\Resources\JobPostings\Pages;

use App\Enums\JobPostingStatus;
use App\Filament\Resources\JobPostings\JobPostingResource;
use App\Models\JobPosting;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListJobPostings extends ListRecords
{
    protected static string $resource = JobPostingResource::class;

    public function getSubheading(): string
    {
        return 'Procesos de selección de personal. Registre cada convocatoria una sola vez y agregue sus documentos (bases, comunicados, resultados) a medida que avanza.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva convocatoria'),
        ];
    }

    public function getTabs(): array
    {
        $overdue = fn (Builder $query) => $query->where('status', JobPostingStatus::EnCurso)->whereDate('ends_at', '<', today());

        return [
            'todas' => Tab::make('Todas')->badge(JobPosting::query()->count()),
            'en-curso' => Tab::make('En curso')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', JobPostingStatus::EnCurso))
                ->badge(JobPosting::query()->where('status', JobPostingStatus::EnCurso)->count())
                ->badgeColor('success'),
            'vencidas' => Tab::make('Plazo vencido')
                ->modifyQueryUsing($overdue)
                ->badge($overdue(JobPosting::query())->count())
                ->badgeColor('danger'),
            'en-evaluacion' => Tab::make('En evaluación')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', JobPostingStatus::EnEvaluacion)),
            'finalizadas' => Tab::make('Finalizadas')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [JobPostingStatus::Finalizada, JobPostingStatus::Desierta, JobPostingStatus::Cancelada])),
        ];
    }
}
