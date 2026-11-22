<?php

namespace App\Filament\Resources\Citizens\Pages;

use App\Filament\Resources\Citizens\CitizenResource;
use Filament\Resources\Pages\ListRecords;

class ListCitizens extends ListRecords
{
    protected static string $resource = CitizenResource::class;

    public function getSubheading(): string
    {
        return 'Personas y organizaciones con cuenta en la Mesa de Partes Virtual. Sus datos los registra y actualiza cada titular; desde aquí solo se puede suspender o reactivar una cuenta.';
    }
}
