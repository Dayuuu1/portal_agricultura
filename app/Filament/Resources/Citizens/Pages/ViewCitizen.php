<?php

namespace App\Filament\Resources\Citizens\Pages;

use App\Filament\Resources\Citizens\CitizenResource;
use App\Models\Citizen;
use App\Models\User;
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
            Action::make('verifyIdentity')
                ->label(fn (): string => $this->record->hasVerifiedIdentity() ? 'Quitar verificación de identidad' : 'Marcar identidad como verificada')
                ->icon(fn (): Heroicon => $this->record->hasVerifiedIdentity() ? Heroicon::OutlinedXCircle : Heroicon::OutlinedIdentification)
                ->color(fn (): string => $this->record->hasVerifiedIdentity() ? 'gray' : 'primary')
                ->authorize('update')
                ->requiresConfirmation()
                ->modalIcon(Heroicon::OutlinedIdentification)
                ->modalDescription(fn (): string => $this->record->hasVerifiedIdentity()
                    ? 'La cuenta volverá a figurar como «identidad sin verificar» en sus expedientes.'
                    : 'Confírmelo solo si comprobó que el '.$this->record->documentLabel().' pertenece a '.$this->record->fullName()
                        .', por ejemplo con el documento original presentado en la entidad o la consulta en línea de RENIEC o SUNAT. Quedará registrado a su nombre.')
                ->action(function (): void {
                    $user = auth()->user();

                    if ($this->record->hasVerifiedIdentity()) {
                        $this->record->clearIdentityVerification();
                        Notification::make()->title('Se quitó la verificación de identidad')->success()->send();

                        return;
                    }

                    abort_unless($user instanceof User, 403);

                    $this->record->markIdentityAsVerified($user);
                    Notification::make()->title('Identidad verificada')->success()->send();
                }),
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
