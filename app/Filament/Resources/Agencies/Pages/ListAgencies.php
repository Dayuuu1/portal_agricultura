<?php

namespace App\Filament\Resources\Agencies\Pages;

use App\Filament\Resources\Agencies\AgencyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAgencies extends ListRecords
{
    protected static string $resource = AgencyResource::class;

    public function getSubheading(): string
    {
        return 'Agencias agrarias que atienden a los productores en provincias. Arrastre las filas para cambiar el orden en que aparecen en el portal.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Agregar agencia'),
        ];
    }
}
