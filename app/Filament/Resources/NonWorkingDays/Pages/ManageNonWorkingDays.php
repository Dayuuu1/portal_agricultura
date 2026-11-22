<?php

namespace App\Filament\Resources\NonWorkingDays\Pages;

use App\Filament\Resources\NonWorkingDays\NonWorkingDayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageNonWorkingDays extends ManageRecords
{
    protected static string $resource = NonWorkingDayResource::class;

    public function getSubheading(): string
    {
        return 'Días no laborables decretados para el sector público. Los feriados nacionales y la Semana Santa ya se descuentan solos. Estos días tampoco cuentan en los plazos de la mesa de partes ni del Libro de Reclamaciones.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Agregar día no laborable'),
        ];
    }
}
