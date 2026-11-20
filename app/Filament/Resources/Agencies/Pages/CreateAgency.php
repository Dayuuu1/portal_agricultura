<?php

namespace App\Filament\Resources\Agencies\Pages;

use App\Filament\Concerns\RedirectsToIndex;
use App\Filament\Resources\Agencies\AgencyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAgency extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = AgencyResource::class;

    public function getSubheading(): string
    {
        return 'Registre la ubicación, el horario y los servicios. Si indica latitud y longitud, el portal mostrará el botón «Ver en el mapa».';
    }
}
