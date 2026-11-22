<?php

namespace App\Filament\Resources\Citizens\Pages;

use App\Filament\Resources\Citizens\CitizenResource;
use App\Models\Citizen;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property Citizen $record
 */
class ViewCitizen extends ViewRecord
{
    protected static string $resource = CitizenResource::class;

    public function getTitle(): string
    {
        return $this->record->fullName();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleActive')
                ->label(fn (): string => $this->record->is_active ? 'Suspender cuenta' : 'Reactivar cuenta')
                ->icon(fn (): Heroicon => $this->record->is_active ? Heroicon::OutlinedNoSymbol : Heroicon::OutlinedCheckCircle)
                ->color(fn (): string => $this->record->is_active ? 'danger' : 'success')
                ->authorize('update')
                ->requiresConfirmation()
                ->modalDescription(fn (): string => $this->record->is_active
                    ? 'La persona no podrá ingresar ni presentar documentos. Sus expedientes se conservan y se siguen atendiendo.'
                    : 'La persona podrá volver a ingresar y presentar documentos.')
                ->action(function (): void {
                    $this->record->update(['is_active' => ! $this->record->is_active]);
                    Notification::make()->title($this->record->is_active ? 'Cuenta reactivada' : 'Cuenta suspendida')->success()->send();
                }),
        ];
    }
}
