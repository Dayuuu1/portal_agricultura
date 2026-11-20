<?php

namespace App\Filament\Resources\OrganizationalUnits\Pages;

use App\Filament\Concerns\RedirectsToIndex;
use App\Filament\Resources\OrganizationalUnits\OrganizationalUnitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganizationalUnit extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = OrganizationalUnitResource::class;

    public function getSubheading(): string
    {
        return 'Indique de qué órgano depende para que aparezca en el lugar correcto del organigrama.';
    }
}
