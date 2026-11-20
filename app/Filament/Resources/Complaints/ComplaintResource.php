<?php

namespace App\Filament\Resources\Complaints;

use App\Enums\ComplaintReason;
use App\Enums\ComplaintStatus;
use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Complaints\Pages\ListComplaints;
use App\Filament\Resources\Complaints\Pages\ViewComplaint;
use App\Models\Complaint;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
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
 * Libro de Reclamaciones (DS N° 007-2020-PCM): claims are read, answered or archived, never edited or deleted.
 */
class ComplaintResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = Complaint::class;

    protected static ?string $slug = 'libro-de-reclamaciones';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Atención al ciudadano';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Libro de reclamaciones';

    protected static ?string $modelLabel = 'reclamo';

    protected static ?string $pluralModelLabel = 'reclamos';

    protected static ?string $recordTitleAttribute = 'code';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $open = Complaint::query()->open()->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return Complaint::query()->overdue()->exists() ? 'danger' : 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Reclamos pendientes de respuesta';
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'full_name', 'document_number'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['Estado' => $record->status->getLabel(), 'Persona' => $record->full_name];
    }

    public static function deadlineLabel(Complaint $record): string
    {
        if (! $record->status->isOpen()) {
            return $record->responded_at ? 'Respondido el '.$record->responded_at->format('d/m/Y') : '—';
        }

        $left = $record->workingDaysLeft();

        return match (true) {
            $left < 0 => 'Vencido hace '.abs($left).' día(s) hábil(es)',
            $left === 0 => 'Vence hoy',
            default => "Quedan {$left} día(s) hábil(es)",
        };
    }

    public static function deadlineColor(Complaint $record): string
    {
        if (! $record->status->isOpen()) {
            return 'gray';
        }

        $left = $record->workingDaysLeft();

        return match (true) {
            $left <= 0 => 'danger',
            $left <= 5 => 'warning',
            default => 'success',
        };
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make('Detalle del reclamo')
                        ->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
                        ->columns(3)
                        ->schema([
                            TextEntry::make('incident_date')->label('Fecha del hecho')->date('d/m/Y'),
                            TextEntry::make('place')->label('Sede')->state(fn (Complaint $record): string => $record->placeLabel()),
                            TextEntry::make('organizationalUnit.name')->label('Oficina')->placeholder('No precisa'),
                            TextEntry::make('reason')->label('Motivo')->badge()->color('gray')->columnSpanFull(),
                            TextEntry::make('description')->label('Lo sucedido')->prose()->columnSpanFull()
                                ->formatStateUsing(fn (?string $state): ?string => $state === null ? null : nl2br(e($state)))->html(),
                            TextEntry::make('request')->label('Pedido')->placeholder('No indicó')->columnSpanFull(),
                            TextEntry::make('attachments')
                                ->label('Documentos adjuntos')
                                ->state(fn (Complaint $record): array => collect($record->attachments ?? [])->pluck('name')->all())
                                ->listWithLineBreaks()
                                ->bulleted()
                                ->placeholder('Sin adjuntos')
                                ->helperText(fn (Complaint $record): ?string => empty($record->attachments) ? null : 'Descárguelos con el botón «Adjuntos» de la parte superior.')
                                ->columnSpanFull(),
                        ]),
                    Section::make('Respuesta')
                        ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                        ->columns(2)
                        ->schema([
                            TextEntry::make('response')->label('Respuesta enviada')->placeholder('Aún no se registra la respuesta.')
                                ->formatStateUsing(fn (?string $state): ?string => $state === null ? null : nl2br(e($state)))->html()->columnSpanFull(),
                            TextEntry::make('responded_at')->label('Fecha de respuesta')->dateTime('d/m/Y H:i')->placeholder('—'),
                            TextEntry::make('responder.name')->label('Registrada por')->placeholder('—'),
                            TextEntry::make('internal_notes')->label('Notas internas (no se envían a la persona)')->placeholder('—')->columnSpanFull(),
                        ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make('Estado')
                        ->icon(Heroicon::OutlinedClock)
                        ->schema([
                            TextEntry::make('status')->label('Estado')->badge(),
                            TextEntry::make('created_at')->label('Registrado')->dateTime('d/m/Y H:i'),
                            TextEntry::make('due_on')->label('Plazo máximo (30 días hábiles)')->date('d/m/Y')
                                ->helperText(fn (Complaint $record): string => self::deadlineLabel($record))
                                ->color(fn (Complaint $record): string => self::deadlineColor($record)),
                        ]),
                    Section::make('Persona')
                        ->icon(Heroicon::OutlinedUser)
                        ->schema([
                            TextEntry::make('full_name')->label('Nombres y apellidos'),
                            TextEntry::make('document')->label('Documento')
                                ->state(fn (Complaint $record): string => $record->document_type->getLabel().' '.$record->document_number),
                            TextEntry::make('guardian_name')->label('Padre, madre o apoderado')
                                ->state(fn (Complaint $record): ?string => $record->is_minor ? "{$record->guardian_name} ({$record->guardian_document_number})" : null)
                                ->visible(fn (Complaint $record): bool => $record->is_minor),
                            TextEntry::make('address')->label('Domicilio'),
                            TextEntry::make('phone')->label('Celular')->url(fn (Complaint $record): string => 'tel:'.preg_replace('/\s+/', '', $record->phone)),
                            TextEntry::make('email')->label('Correo')->placeholder('No indicó')->copyable(),
                            IconEntry::make('notify_by_email')->label('Autorizó notificación por correo')->boolean(),
                        ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['agency', 'organizationalUnit']))
            ->recordUrl(fn (Complaint $record): string => static::getUrl('view', ['record' => $record]))
            ->emptyStateIcon(Heroicon::OutlinedBookOpen)
            ->emptyStateHeading('No hay reclamos en esta vista')
            ->emptyStateDescription('Los reclamos que la ciudadanía registre en el portal aparecerán aquí y llegarán al correo configurado.')
            ->columns([
                TextColumn::make('code')->label('N° de reclamo')->searchable()->weight('semibold')
                    ->description(fn (Complaint $record): string => $record->created_at->format('d/m/Y H:i')),
                TextColumn::make('full_name')->label('Persona')->searchable(['full_name', 'document_number'])
                    ->description(fn (Complaint $record): string => $record->document_type->getLabel().' '.$record->document_number),
                TextColumn::make('reason')->label('Motivo')->badge()->color('gray')->wrap(),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('due_on')->label('Plazo')->date('d/m/Y')->sortable()
                    ->description(fn (Complaint $record): string => self::deadlineLabel($record))
                    ->color(fn (Complaint $record): string => self::deadlineColor($record)),
                TextColumn::make('place')->label('Sede / oficina')->toggleable(isToggledHiddenByDefault: true)
                    ->state(fn (Complaint $record): string => $record->placeLabel())
                    ->description(fn (Complaint $record): ?string => $record->organizationalUnit?->name),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(ComplaintStatus::class)->multiple(),
                SelectFilter::make('reason')->label('Motivo')->options(ComplaintReason::class),
            ])
            ->recordActions([
                ViewAction::make()->label('Atender'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComplaints::route('/'),
            'view' => ViewComplaint::route('/{record}'),
        ];
    }
}
