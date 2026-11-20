<?php

namespace App\Filament\Resources\Complaints\Pages;

use App\Enums\ComplaintStatus;
use App\Filament\Resources\Complaints\ComplaintResource;
use App\Mail\ComplaintAnswered;
use App\Models\Complaint;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * @property Complaint $record
 */
class ViewComplaint extends ViewRecord
{
    protected static string $resource = ComplaintResource::class;

    public function getTitle(): string
    {
        return "Reclamo {$this->record->code}";
    }

    public function getSubheading(): string
    {
        return $this->record->status->isOpen()
            ? ComplaintResource::deadlineLabel($this->record).' para responder (vence el '.$this->record->due_on->format('d/m/Y').').'
            : $this->record->status->getLabel().'.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->attachmentsAction(),
            Action::make('changeStatus')
                ->label('Cambiar estado')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->authorize('update')
                ->visible(fn (): bool => $this->record->status->isOpen())
                ->modalDescription('Use «Derivado» si otra entidad es la competente y «Archivado» si la persona no completó los datos en 2 días hábiles o desistió del reclamo (arts. 16.1 y 16.3).')
                ->fillForm(fn (): array => ['status' => $this->record->status, 'internal_notes' => $this->record->internal_notes])
                ->schema([
                    Select::make('status')
                        ->label('Nuevo estado')
                        ->options(collect([ComplaintStatus::Registrado, ComplaintStatus::EnAtencion, ComplaintStatus::Derivado, ComplaintStatus::Archivado])
                            ->mapWithKeys(fn (ComplaintStatus $status): array => [$status->value => $status->getLabel()])->all())
                        ->required(),
                    Textarea::make('internal_notes')
                        ->label('Notas internas')
                        ->helperText('A qué oficina se derivó, a qué entidad, motivo del archivo, etc. No se envían a la persona.')
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    $this->record->update($data);
                    Notification::make()->title('Estado actualizado')->success()->send();
                }),
            Action::make('respond')
                ->label('Registrar respuesta')
                ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                ->authorize('update')
                ->visible(fn (): bool => $this->record->status->isOpen())
                ->modalHeading('Respuesta al reclamo')
                ->modalDescription(fn (): string => $this->record->canBeNotifiedByEmail()
                    ? "La persona autorizó la notificación por correo: la respuesta se enviará automáticamente a {$this->record->email}."
                    : "La persona NO autorizó el correo: después de guardar, notifique la respuesta por escrito en su domicilio ({$this->record->address}).")
                ->modalSubmitActionLabel('Guardar y notificar')
                ->schema([
                    ToggleButtons::make('status')
                        ->label('Resultado')
                        ->options([
                            ComplaintStatus::Aceptado->value => 'Aceptado',
                            ComplaintStatus::Denegado->value => 'Denegado',
                        ])
                        ->colors([ComplaintStatus::Aceptado->value => 'success', ComplaintStatus::Denegado->value => 'danger'])
                        ->inline()
                        ->required(),
                    Textarea::make('response')
                        ->label('Respuesta')
                        ->helperText('Respuesta motivada. Si se acepta, indique las medidas correctivas adoptadas (art. 16.2).')
                        ->rows(10)
                        ->required()
                        ->maxLength(5000),
                ])
                ->action(fn (array $data) => $this->respond($data)),
        ];
    }

    /**
     * @param  array{status: string, response: string}  $data
     */
    private function respond(array $data): void
    {
        $this->record->update([
            ...$data,
            'responded_at' => now(),
            'responded_by' => auth()->id(),
        ]);

        if (! $this->record->canBeNotifiedByEmail()) {
            Notification::make()
                ->title('Respuesta registrada')
                ->body('Recuerde notificarla por escrito en el domicilio de la persona: '.$this->record->address)
                ->warning()
                ->persistent()
                ->send();

            return;
        }

        try {
            Mail::to($this->record->email, $this->record->full_name)->send(new ComplaintAnswered($this->record));
            Notification::make()->title('Respuesta enviada a '.$this->record->email)->success()->send();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()
                ->title('La respuesta se guardó, pero el correo no se pudo enviar')
                ->body('Revise la configuración de correo o notifíquela por otro medio.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    private function attachmentsAction(): ActionGroup
    {
        $files = $this->record->attachments ?? [];

        return ActionGroup::make(collect($files)->map(fn (array $file, int $index): Action => Action::make("attachment{$index}")
            ->label($file['name'])
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->authorize('view')
            ->action(fn (): StreamedResponse => Storage::disk('local')->download($file['path'], $file['name'])))->all())
            ->label('Adjuntos ('.count($files).')')
            ->icon(Heroicon::OutlinedPaperClip)
            ->color('gray')
            ->button()
            ->visible($files !== []);
    }
}
