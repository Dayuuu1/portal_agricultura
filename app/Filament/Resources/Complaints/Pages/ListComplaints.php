<?php

namespace App\Filament\Resources\Complaints\Pages;

use App\Enums\ComplaintStatus;
use App\Filament\Resources\Complaints\ComplaintResource;
use App\Models\Complaint;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListComplaints extends ListRecords
{
    protected static string $resource = ComplaintResource::class;

    public function getSubheading(): string
    {
        return 'Reclamos registrados en el Libro de Reclamaciones del portal (DS N° 007-2020-PCM). Plazos: 3 días hábiles para evaluar y derivar, y 30 días hábiles como máximo para responder. Los reclamos no se eliminan: se responden o se archivan.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publicForm')
                ->label('Ver formulario público')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(route('complaints.create'))
                ->openUrlInNewTab(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'por-atender' => Tab::make('Por atender')
                ->icon(Heroicon::OutlinedInboxArrowDown)
                ->modifyQueryUsing(fn (Builder $query) => $query->open())
                ->badge(Complaint::query()->open()->count())
                ->badgeColor('warning'),
            'vencidos' => Tab::make('Plazo vencido')
                ->modifyQueryUsing(fn (Builder $query) => $query->overdue())
                ->badge(Complaint::query()->overdue()->count())
                ->badgeColor('danger'),
            'respondidos' => Tab::make('Respondidos')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [ComplaintStatus::Aceptado, ComplaintStatus::Denegado])),
            'otros' => Tab::make('Derivados y archivados')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [ComplaintStatus::Derivado, ComplaintStatus::Archivado])),
            'todos' => Tab::make('Todos')->badge(Complaint::query()->count()),
        ];
    }
}
