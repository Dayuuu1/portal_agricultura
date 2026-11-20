<?php

namespace App\Filament\Resources\OrganizationalUnits\Pages;

use App\Filament\Resources\OrganizationalUnits\OrganizationalUnitResource;
use App\Models\OrganizationalUnit;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrganizationalUnits extends ListRecords
{
    protected static string $resource = OrganizationalUnitResource::class;

    public function getSubheading(): string
    {
        return 'Órganos y unidades del organigrama y el directorio del portal. Mantenga al día a los responsables y sus datos de contacto.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Agregar unidad'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todas' => Tab::make('Todas')->badge(OrganizationalUnit::query()->count()),
            'sin-responsable' => Tab::make('Sin responsable')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('head_name'))
                ->badge(OrganizationalUnit::query()->whereNull('head_name')->count())
                ->badgeColor('warning'),
        ];
    }
}
