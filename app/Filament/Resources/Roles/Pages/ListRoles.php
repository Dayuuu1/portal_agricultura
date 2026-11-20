<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    public function getSubheading(): string
    {
        return 'Un rol agrupa los permisos de un puesto de trabajo (qué módulos ve, si puede crear, editar, eliminar o publicar y qué tipos de documento maneja). Asigne roles a cada persona desde «Usuarios».';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo rol'),
        ];
    }
}
