<?php

namespace App\Filament\Resources\ProcedureTypes\Pages;

use App\Filament\Resources\ProcedureTypes\ProcedureTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageProcedureTypes extends ManageRecords
{
    protected static string $resource = ProcedureTypeResource::class;

    public function getSubheading(): string
    {
        return 'Lo que la ciudadanía puede presentar por la Mesa de Partes Virtual, con su plazo y requisitos. Un tipo con expedientes no se elimina: desactívelo para que deje de ofrecerse.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo tipo de trámite'),
        ];
    }
}
