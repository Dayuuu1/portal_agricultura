<?php

namespace App\Filament\Resources\Submissions\Pages;

use App\Enums\SubmissionStatus;
use App\Filament\Resources\Submissions\SubmissionResource;
use App\Models\OrganizationalUnit;
use App\Models\Submission;
use App\Models\User;
use App\Support\BusinessDays;
use App\Support\MesaPartes\MesaPartesConfig;
use App\Support\MesaPartes\SubmissionWorkflow;
use Closure;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property Submission $record
 */
class ViewSubmission extends ViewRecord
{
    protected static string $resource = SubmissionResource::class;

    public function getTitle(): string
    {
        return "Expediente {$this->record->code}";
    }

    public function getSubheading(): string
    {
        return $this->record->subject.' · '.$this->record->citizen->fullName();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->observeAction(),
            $this->deriveAction(),
            $this->answerAction(),
            ActionGroup::make([
                Action::make('startAttention')
                    ->label('Marcar en atención')
                    ->icon(Heroicon::OutlinedClock)
                    ->authorize('update')
                    ->visible(fn (): bool => $this->record->status === SubmissionStatus::Derivado)
                    ->requiresConfirmation()
                    ->modalDescription('La oficina empezó a atender el documento. El administrado lo verá en la hoja de ruta.')
                    ->action(fn () => $this->run(fn (User $user) => SubmissionWorkflow::startAttention($this->record, $user), 'Expediente en atención')),
                Action::make('notFiled')
                    ->label('Declarar no presentado')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->authorize('update')
                    ->visible(fn (): bool => $this->record->status === SubmissionStatus::Observado && ! $this->record->canBeCured())
                    ->modalDescription('El plazo para subsanar venció sin respuesta. Se tendrá el documento por no presentado y se notificará al administrado (TUO de la Ley N° 27444, art. 136).')
                    ->schema([Textarea::make('note')->label('Nota para el administrado (opcional)')->rows(3)->maxLength(2000)])
                    ->action(fn (array $data) => $this->run(fn (User $user) => SubmissionWorkflow::declareNotFiled($this->record, $data['note'] ?? null, $user), 'Documento declarado no presentado')),
                Action::make('archive')
                    ->label('Archivar')
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->color('gray')
                    ->authorize('update')
                    ->visible(fn (): bool => $this->record->status->isOpen())
                    ->modalDescription('Úselo cuando el trámite concluye sin una respuesta de fondo (por ejemplo, desistimiento o duplicado). Se notificará al administrado con el motivo.')
                    ->schema([Textarea::make('reason')->label('Motivo')->required()->rows(3)->maxLength(2000)])
                    ->action(fn (array $data) => $this->run(fn (User $user) => SubmissionWorkflow::archive($this->record, $data['reason'], $user), 'Expediente archivado')),
                Action::make('note')
                    ->label('Agregar nota interna')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->authorize('update')
                    ->modalDescription('Queda en la hoja de ruta del panel. El administrado no la ve.')
                    ->schema([Textarea::make('note')->label('Nota')->required()->rows(3)->maxLength(2000)])
                    ->action(function (array $data): void {
                        $this->run(fn (User $user) => SubmissionWorkflow::addNote($this->record, $data['note'], $user), 'Nota agregada');
                    }),
            ])->label('Más acciones')->button()->color('gray')->icon(Heroicon::OutlinedEllipsisVertical),
        ];
    }

    private function observeAction(): Action
    {
        return Action::make('observe')
            ->label('Observar')
            ->icon(Heroicon::OutlinedExclamationTriangle)
            ->color('warning')
            ->authorize('update')
            ->visible(fn (): bool => in_array($this->record->status, [SubmissionStatus::Recibido, SubmissionStatus::Subsanado], true))
            ->modalHeading('Observar el documento')
            ->modalDescription(fn (): string => 'Indique qué falta o qué debe corregirse. El administrado tendrá '.MesaPartesConfig::cureDays()
                .' días hábiles para subsanarlo, hasta el '.BusinessDays::after(today(), MesaPartesConfig::cureDays())->translatedFormat('l j \d\e F').'.'
                .($this->record->citizen->acceptsElectronicNotifications() ? '' : ' El administrado NO autorizó la notificación electrónica: notifíquele también en su domicilio.'))
            ->schema([
                Textarea::make('observation')->label('Observación')->required()->rows(5)->maxLength(3000)
                    ->placeholder('Ej.: Falta la firma en el escrito. Adjunte el documento firmado.'),
            ])
            ->modalSubmitActionLabel('Observar y notificar')
            ->action(fn (array $data) => $this->run(fn (User $user) => SubmissionWorkflow::observe($this->record, $data['observation'], $user), 'Observación notificada'));
    }

    private function deriveAction(): Action
    {
        return Action::make('derive')
            ->label(fn (): string => in_array($this->record->status, [SubmissionStatus::Derivado, SubmissionStatus::EnAtencion], true) ? 'Derivar a otra oficina' : 'Admitir y derivar')
            ->icon(Heroicon::OutlinedArrowRightCircle)
            ->color('primary')
            ->authorize('update')
            ->visible(fn (): bool => in_array($this->record->status, [SubmissionStatus::Recibido, SubmissionStatus::Subsanado, SubmissionStatus::Derivado, SubmissionStatus::EnAtencion], true))
            ->modalDescription('La oficina recibirá el documento para atenderlo. El administrado verá a qué oficina se derivó.')
            ->fillForm(fn (): array => ['unit_id' => $this->record->current_unit_id ?? $this->record->target_unit_id ?? $this->record->procedureType->default_unit_id])
            ->schema([
                Select::make('unit_id')->label('Oficina')->required()->searchable()
                    ->options(fn (): array => OrganizationalUnit::query()->active()->orderBy('name')->pluck('name', 'id')->all()),
                Textarea::make('note')->label('Proveído o indicación interna (opcional)')->rows(3)->maxLength(2000)
                    ->helperText('Queda en la hoja de ruta del panel; el administrado solo ve la oficina.'),
            ])
            ->modalSubmitActionLabel('Derivar')
            ->action(function (array $data): void {
                $unit = OrganizationalUnit::query()->findOrFail($data['unit_id']);
                $this->run(fn (User $user) => SubmissionWorkflow::derive($this->record, $unit, $data['note'] ?? null, $user), "Derivado a {$unit->name}");
            });
    }

    private function answerAction(): Action
    {
        return Action::make('answer')
            ->label('Registrar respuesta')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->authorize('update')
            ->visible(fn (): bool => in_array($this->record->status, [SubmissionStatus::Recibido, SubmissionStatus::Subsanado, SubmissionStatus::Derivado, SubmissionStatus::EnAtencion], true))
            ->modalHeading('Registrar la respuesta de la entidad')
            ->modalDescription(fn (): string => 'Se notificará al administrado y el expediente quedará como atendido.'
                .($this->record->citizen->acceptsElectronicNotifications() ? '' : ' El administrado NO autorizó la notificación electrónica: entréguele también la respuesta en su domicilio.'))
            ->schema([
                Textarea::make('response')->label('Respuesta')->required()->rows(5)->maxLength(5000)
                    ->helperText('Resumen de lo resuelto. Si hay un documento firmado (oficio, resolución), adjúntelo.'),
                FileUpload::make('document')->label('Documento de respuesta (PDF)')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(fn (): int => MesaPartesConfig::maxFileKilobytes())
                    ->storeFiles(false),
            ])
            ->modalSubmitActionLabel('Registrar y notificar')
            ->action(fn (array $data) => $this->run(fn (User $user) => SubmissionWorkflow::answer($this->record, $data['response'], $data['document'] ?? null, $user), 'Respuesta registrada y notificada'));
    }

    /**
     * Runs a workflow step for the signed-in user and reports the outcome; rules of the workflow come back as warnings.
     *
     * @param  Closure(User): mixed  $step
     */
    private function run(Closure $step, string $success): void
    {
        /** @var User $user */
        $user = auth()->user();

        try {
            $step($user);
        } catch (DomainException $exception) {
            Notification::make()->title('No se pudo completar la acción')->body($exception->getMessage())->warning()->send();

            return;
        }

        $this->record->refresh();
        Notification::make()->title($success)->success()->send();
    }
}
