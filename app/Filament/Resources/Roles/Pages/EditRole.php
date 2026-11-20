<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Role;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property Role $record
 */
class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public function getSubheading(): string
    {
        $count = $this->record->users()->count();

        return $count > 0
            ? "Asignado a {$count} usuario(s). Los cambios se aplican de inmediato a todos ellos."
            : 'Este rol aún no está asignado a ningún usuario.';
    }

    /**
     * Shows the permissions implied on save (e.g. "Publicar" also ticks "Editar" and "Ver").
     */
    protected function afterSave(): void
    {
        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('users')
                ->label('Ver usuarios')
                ->icon(Heroicon::OutlinedUsers)
                ->color('gray')
                ->url(fn (): string => UserResource::getUrl(parameters: ['filters' => ['roles' => ['values' => [$this->record->id]]]])),
            DeleteAction::make(),
        ];
    }
}
