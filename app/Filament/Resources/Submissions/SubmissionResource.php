<?php

namespace App\Filament\Resources\Submissions;

use App\Enums\SubmissionStatus;
use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Citizens\CitizenResource;
use App\Filament\Resources\Submissions\Pages\ListSubmissions;
use App\Filament\Resources\Submissions\Pages\ViewSubmission;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\SubmissionMovement;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Expedientes of the Mesa de Partes Virtual. They are received by the citizens, so the panel never creates,
 * edits or deletes them: it observes, derives, answers or archives them, and every step stays on the route.
 */
class SubmissionResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = Submission::class;

    protected static ?string $slug = 'mesa-de-partes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Mesa de partes';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Expedientes';

    protected static ?string $modelLabel = 'expediente';

    protected static ?string $pluralModelLabel = 'expedientes';

    protected static ?string $recordTitleAttribute = 'code';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Submission::query()->toReview()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Documentos por revisar';
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'subject'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['Asunto' => $record->subject, 'Estado' => $record->status->getLabel()];
    }

    /**
     * Remaining working days in words; the table omits what the term is for, since the status says it.
     */
    public static function deadlineLabel(Submission $record, bool $withPurpose = true): string
    {
        $deadline = $record->currentDeadline();

        if ($deadline === null) {
            return $record->status->isOpen() ? 'Sin plazo propio' : ($record->closed_at ? 'Cerrado el '.$record->closed_at->format('d/m/Y') : '—');
        }

        $left = (int) $record->workingDaysLeft();
        $purpose = $withPurpose ? ($record->status === SubmissionStatus::Observado ? ' para subsanar' : ' para atender') : '';

        return match (true) {
            $left < 0 => 'Vencido hace '.self::workingDays(abs($left)),
            $left === 0 => "Vence hoy{$purpose}",
            $left === 1 => "Queda 1 día hábil{$purpose}",
            default => 'Quedan '.self::workingDays($left).$purpose,
        };
    }

    private static function workingDays(int $days): string
    {
        return $days === 1 ? '1 día hábil' : "{$days} días hábiles";
    }

    public static function deadlineColor(Submission $record): string
    {
        $left = $record->workingDaysLeft();

        return match (true) {
            $left === null => 'gray',
            $left <= 0 => 'danger',
            $left <= 3 => 'warning',
            default => 'success',
        };
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make('Documento')
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->columns(2)
                        ->schema([
                            TextEntry::make('procedureType.name')->label('Tipo de trámite'),
                            TextEntry::make('related_code')->label('Expediente relacionado')->placeholder('—'),
                            TextEntry::make('subject')->label('Asunto')->weight('semibold')->columnSpanFull(),
                            TextEntry::make('description')->label('Fundamentos o descripción')->prose()->columnSpanFull()
                                ->formatStateUsing(fn (string $state): string => nl2br(e($state)))->html(),
                            TextEntry::make('targetUnit.name')->label('Oficina indicada por el administrado')->placeholder('No indicó'),
                            TextEntry::make('currentUnit.name')->label('Oficina actual')->placeholder('Mesa de partes'),
                        ]),
                    Section::make('Archivos')
                        ->icon(Heroicon::OutlinedPaperClip)
                        ->description('Se descargan desde el almacenamiento privado. La huella SHA-256 es la que figura en el cargo del administrado.')
                        ->schema([
                            RepeatableEntry::make('files')
                                ->hiddenLabel()
                                ->schema([
                                    TextEntry::make('original_name')->hiddenLabel()->weight('semibold')
                                        ->icon(Heroicon::OutlinedArrowDownTray)
                                        ->url(fn (SubmissionFile $record): string => route('admin.mesa-partes.file', $record))
                                        ->openUrlInNewTab()
                                        ->color('primary'),
                                    TextEntry::make('kind')->hiddenLabel()->badge()->color('gray'),
                                    TextEntry::make('details')->hiddenLabel()->color('gray')
                                        ->state(fn (SubmissionFile $record): string => $record->humanSize().' · '.($record->pages === 1 ? '1 folio' : $record->pages.' folios').' · '.$record->created_at->format('d/m/Y H:i')),
                                    TextEntry::make('sha256')->hiddenLabel()->color('gray')->size('xs')->fontFamily('mono')->copyable(),
                                ])
                                ->columns(2)
                                ->contained(false),
                        ]),
                    Section::make('Respuesta')
                        ->icon(Heroicon::OutlinedCheckBadge)
                        ->visible(fn (Submission $record): bool => filled($record->response))
                        ->schema([
                            TextEntry::make('answered_at')->label('Emitida el')->dateTime('d/m/Y H:i'),
                            TextEntry::make('response')->hiddenLabel()->prose()->formatStateUsing(fn (string $state): string => nl2br(e($state)))->html(),
                        ]),
                    Section::make('Hoja de ruta')
                        ->icon(Heroicon::OutlinedMap)
                        ->schema([
                            RepeatableEntry::make('movements')
                                ->hiddenLabel()
                                ->schema([
                                    TextEntry::make('action')->hiddenLabel()->weight('semibold')
                                        ->formatStateUsing(fn (SubmissionMovement $record): string => $record->actionLabel())
                                        ->badge(fn (SubmissionMovement $record): bool => ! $record->is_public)
                                        ->color(fn (SubmissionMovement $record): string => $record->is_public ? 'primary' : 'gray')
                                        ->helperText(fn (SubmissionMovement $record): ?string => $record->is_public ? null : 'Interno: el administrado no lo ve'),
                                    TextEntry::make('created_at')->hiddenLabel()->dateTime('d/m/Y H:i')->color('gray')
                                        ->helperText(fn (SubmissionMovement $record): string => $record->user?->name ?? 'Administrado'),
                                    TextEntry::make('unit.name')->hiddenLabel()->placeholder('')->icon(Heroicon::OutlinedBuildingOffice2)->columnSpanFull()
                                        ->visible(fn (SubmissionMovement $record): bool => $record->unit_id !== null),
                                    TextEntry::make('note')->hiddenLabel()->columnSpanFull()->color('gray')
                                        ->formatStateUsing(fn (string $state): string => nl2br(e($state)))->html()
                                        ->visible(fn (SubmissionMovement $record): bool => filled($record->note)),
                                ])
                                ->columns(2),
                        ]),
                ])->columnSpan(['default' => 3, 'lg' => 2]),
                Group::make([
                    Section::make('Estado')
                        ->icon(Heroicon::OutlinedFlag)
                        ->schema([
                            TextEntry::make('status')->label('Estado')->badge(),
                            TextEntry::make('deadline')->label('Plazo')
                                ->state(fn (Submission $record): string => self::deadlineLabel($record))
                                ->color(fn (Submission $record): string => self::deadlineColor($record))
                                ->helperText(fn (Submission $record): ?string => $record->currentDeadline()?->translatedFormat('l j \d\e F \d\e Y')),
                            TextEntry::make('observation')->label('Observación vigente')->visible(fn (Submission $record): bool => $record->status === SubmissionStatus::Observado)
                                ->formatStateUsing(fn (string $state): string => nl2br(e($state)))->html(),
                            Grid::make(2)->schema([
                                TextEntry::make('submitted_at')->label('Enviado')->dateTime('d/m/Y H:i'),
                                TextEntry::make('filed_on')->label('Fecha de presentación')->date('d/m/Y'),
                                TextEntry::make('due_on')->label('Plazo de atención')->date('d/m/Y')->placeholder('Sin plazo propio'),
                                TextEntry::make('folios')->label('Folios'),
                            ]),
                        ]),
                    Section::make('Administrado')
                        ->icon(Heroicon::OutlinedUser)
                        ->schema([
                            TextEntry::make('citizen.name')->label(fn (Submission $record): string => $record->citizen->person_type->getLabel())
                                ->state(fn (Submission $record): string => $record->citizen->fullName())->weight('semibold'),
                            TextEntry::make('citizen.document')->label('Documento')->state(fn (Submission $record): string => $record->citizen->documentLabel()),
                            TextEntry::make('citizen.identity')->label('Identidad')
                                ->state(fn (Submission $record): string => $record->citizen->hasVerifiedIdentity() ? 'Verificada' : 'Sin verificar')
                                ->badge()
                                ->color(fn (Submission $record): string => $record->citizen->hasVerifiedIdentity() ? 'success' : 'warning')
                                ->helperText(fn (Submission $record): ?string => $record->citizen->hasVerifiedIdentity()
                                    ? null
                                    : 'Nadie comprobó aún que el documento pertenece a quien presenta. Hágalo antes de admitir su primer escrito y márquelo en la ficha del administrado.')
                                ->url(fn (Submission $record): ?string => auth()->user()?->can('view', $record->citizen) ? CitizenResource::getUrl('view', ['record' => $record->citizen]) : null),
                            TextEntry::make('citizen.representative_name')->label('Representante legal')->visible(fn (Submission $record): bool => filled($record->citizen->representative_name)),
                            TextEntry::make('citizen.email')->label('Correo')->copyable(),
                            TextEntry::make('citizen.phone')->label('Celular'),
                            TextEntry::make('citizen.address')->label('Domicilio')
                                ->state(fn (Submission $record): string => collect([$record->citizen->address, $record->citizen->district, $record->citizen->province, $record->citizen->department])->filter()->implode(', ')),
                            IconEntry::make('citizen.e_notifications')->label('Notificación electrónica autorizada')->boolean()
                                ->state(fn (Submission $record): bool => $record->citizen->acceptsElectronicNotifications())
                                ->helperText(fn (Submission $record): ?string => $record->citizen->acceptsElectronicNotifications() ? null : 'Las observaciones y la respuesta quedan en su cuenta, pero deben notificarse también en su domicilio.'),
                        ]),
                ])->columnSpan(['default' => 3, 'lg' => 1]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['citizen', 'procedureType', 'currentUnit']))
            ->recordUrl(fn (Submission $record): string => static::getUrl('view', ['record' => $record]))
            ->emptyStateIcon(Heroicon::OutlinedInboxArrowDown)
            ->emptyStateHeading('No hay expedientes en esta vista')
            ->emptyStateDescription('Los documentos que la ciudadanía presente en la Mesa de Partes Virtual aparecerán aquí.')
            ->columns([
                TextColumn::make('code')->label('Expediente')->searchable()->weight('semibold')
                    ->description(fn (Submission $record): string => 'Presentado el '.$record->filed_on->format('d/m/Y')),
                TextColumn::make('citizen.first_name')->label('Administrado')
                    ->state(fn (Submission $record): string => $record->citizen->fullName())
                    ->description(fn (Submission $record): string => $record->citizen->documentLabel())
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('citizen', fn (Builder $citizen) => $citizen
                        ->where('document_number', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")->orWhere('business_name', 'like', "%{$search}%"))),
                TextColumn::make('subject')->label('Asunto')->searchable()->limit(90)->wrap()->grow()
                    ->extraAttributes(['class' => 'min-w-48'])
                    ->description(fn (Submission $record): string => $record->procedureType->name),
                TextColumn::make('currentUnit.name')->label('Oficina')->placeholder('Mesa de partes')->toggleable(),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('deadline')->label('Plazo')
                    ->state(fn (Submission $record): ?string => $record->currentDeadline()?->format('d/m/Y'))
                    ->placeholder('—')
                    ->description(fn (Submission $record): string => self::deadlineLabel($record, withPurpose: false))
                    ->color(fn (Submission $record): string => self::deadlineColor($record)),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(SubmissionStatus::class)->multiple(),
                SelectFilter::make('procedure_type_id')->label('Tipo de trámite')->relationship('procedureType', 'name'),
                SelectFilter::make('current_unit_id')->label('Oficina')->relationship('currentUnit', 'name')->searchable()->preload(),
            ])
            ->recordActions([
                ViewAction::make()->label('Atender')->iconButton()->icon(Heroicon::OutlinedArrowRightCircle),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubmissions::route('/'),
            'view' => ViewSubmission::route('/{record}'),
        ];
    }
}
