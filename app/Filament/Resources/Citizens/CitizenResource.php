<?php

namespace App\Filament\Resources\Citizens;

use App\Enums\PersonType;
use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Citizens\Pages\ListCitizens;
use App\Filament\Resources\Citizens\Pages\ViewCitizen;
use App\Filament\Resources\Submissions\SubmissionResource;
use App\Models\Citizen;
use App\Models\Submission;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Accounts of the Mesa de Partes Virtual. Their data belong to the citizen: the panel reads them and can only
 * suspend or reactivate an account.
 */
class CitizenResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = Citizen::class;

    protected static ?string $slug = 'administrados';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Mesa de partes';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'administrado';

    protected static ?string $pluralModelLabel = 'administrados';

    protected static bool $isGloballySearchable = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Identificación y contacto')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label(fn (Citizen $record): string => $record->person_type->getLabel())
                            ->state(fn (Citizen $record): string => $record->fullName())->weight('semibold'),
                        TextEntry::make('document')->label('Documento')->state(fn (Citizen $record): string => $record->documentLabel()),
                        TextEntry::make('representative_name')->label('Representante legal')
                            ->state(fn (Citizen $record): string => $record->representative_name.' ('.$record->representative_document_number.')')
                            ->visible(fn (Citizen $record): bool => $record->person_type === PersonType::Juridica),
                        TextEntry::make('email')->label('Correo')->copyable(),
                        TextEntry::make('phone')->label('Celular'),
                        TextEntry::make('address')->label('Domicilio')->columnSpanFull()
                            ->state(fn (Citizen $record): string => collect([$record->address, $record->district, $record->province, $record->department])->filter()->implode(', ')),
                    ]),
                Section::make('Cuenta y consentimientos')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columns(2)
                    ->schema([
                        IconEntry::make('is_active')->label('Cuenta activa')->boolean(),
                        TextEntry::make('email_verified_at')->label('Correo confirmado')->dateTime('d/m/Y H:i')->placeholder('Sin confirmar'),
                        TextEntry::make('privacy_accepted_at')->label('Aceptó el tratamiento de datos')->dateTime('d/m/Y H:i'),
                        TextEntry::make('truthfulness_declared_at')->label('Declaración de veracidad')->dateTime('d/m/Y H:i'),
                        TextEntry::make('e_notifications_authorized_at')->label('Notificación electrónica autorizada')->dateTime('d/m/Y H:i')->placeholder('No autorizada: notificar en el domicilio'),
                        TextEntry::make('last_login_at')->label('Último ingreso')->dateTime('d/m/Y H:i')->placeholder('—'),
                    ]),
                Section::make('Expedientes')
                    ->icon(Heroicon::OutlinedInboxArrowDown)
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('submissions')
                            ->hiddenLabel()
                            ->placeholder('Aún no presentó documentos.')
                            ->schema([
                                TextEntry::make('code')->hiddenLabel()->weight('semibold')->color('primary')
                                    ->url(fn (Submission $record): string => SubmissionResource::getUrl('view', ['record' => $record])),
                                TextEntry::make('subject')->hiddenLabel(),
                                TextEntry::make('status')->hiddenLabel()->badge(),
                                TextEntry::make('filed_on')->hiddenLabel()->date('d/m/Y')->color('gray'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon(Heroicon::OutlinedUsers)
            ->emptyStateHeading('Aún no hay cuentas')
            ->emptyStateDescription('Las personas que se registren en la Mesa de Partes Virtual aparecerán aquí.')
            ->columns([
                TextColumn::make('first_name')->label('Administrado')
                    ->state(fn (Citizen $record): string => $record->fullName())->weight('semibold')
                    ->description(fn (Citizen $record): string => $record->documentLabel())
                    ->searchable(['first_name', 'last_name', 'business_name', 'document_number']),
                TextColumn::make('email')->label('Correo')->searchable()
                    ->description(fn (Citizen $record): string => $record->hasVerifiedEmail() ? 'Confirmado' : 'Sin confirmar'),
                IconColumn::make('e_notifications_authorized_at')->label('Notif. electrónica')->boolean()
                    ->state(fn (Citizen $record): bool => $record->acceptsElectronicNotifications())->alignCenter(),
                TextColumn::make('submissions_count')->label('Expedientes')->counts('submissions')->badge()->color('gray')->alignCenter(),
                IconColumn::make('is_active')->label('Activa')->boolean()->alignCenter(),
                TextColumn::make('created_at')->label('Registro')->date('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('person_type')->label('Tipo de persona')->options(PersonType::class),
                TernaryFilter::make('email_verified_at')->label('Correo confirmado')->nullable(),
                TernaryFilter::make('is_active')->label('Cuenta activa'),
            ])

            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCitizens::route('/'),
            'view' => ViewCitizen::route('/{record}'),
        ];
    }
}
