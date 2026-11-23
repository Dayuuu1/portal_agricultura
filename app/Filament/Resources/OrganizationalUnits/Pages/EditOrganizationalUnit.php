<?php

namespace App\Filament\Resources\OrganizationalUnits\Pages;

use App\Filament\Concerns\HasEditHeaderActions;
use App\Filament\Resources\OrganizationalUnits\OrganizationalUnitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrganizationalUnit extends EditRecord
{
    use HasEditHeaderActions;

    protected static string $resource = OrganizationalUnitResource::class;

    public function getSubheading(): string
    {
        return 'La presentación, las funciones y el responsable se muestran en la ficha pública de la unidad y en el directorio.';
    }

    protected function publicUrl(): ?string
    {
        return $this->record->is_active ? route('institutional.unit', $this->record) : null;
    }

    protected function configureDeleteAction(DeleteAction $action): DeleteAction
    {
        return OrganizationalUnitResource::configureDeleteAction($action);
    }
}
