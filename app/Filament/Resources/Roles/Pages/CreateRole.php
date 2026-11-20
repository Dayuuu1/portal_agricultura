<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Concerns\RedirectsToIndex;
use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = RoleResource::class;

    public function getSubheading(): string
    {
        return 'Dé solo los permisos necesarios para el puesto. Podrá ajustarlos después y el cambio se aplica de inmediato a todas las personas con este rol.';
    }
}
