<?php

namespace App\Filament\Resources\ProcedureTypes;

use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\ProcedureTypes\Pages\ManageProcedureTypes;
use App\Models\OrganizationalUnit;
use App\Models\ProcedureType;
use App\Support\MesaPartes\MesaPartesConfig;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Catalog of what the Mesa de Partes receives, with the term and requirements of each kind (TUPA, general
 * requests, access to public information…). A type already used by expedientes is deactivated, not deleted.
 */
class ProcedureTypeResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = ProcedureType::class;

    protected static ?string $slug = 'tipos-de-tramite';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Mesa de partes';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'tipo de trámite';

    protected static ?string $pluralModelLabel = 'tipos de trámite';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nombre')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->label('Descripción')->rows(2)->maxLength(1000)->columnSpanFull()
                    ->helperText('Explica a la ciudadanía cuándo elegir este tipo.'),
                Textarea::make('requirements')->label('Requisitos')->rows(4)->maxLength(4000)->columnSpanFull()
                    ->helperText('Un requisito por línea. Solo los que establezca el TUPA o una norma (principio de legalidad).'),
                TextInput::make('response_days')->label('Plazo de atención (días hábiles)')->numeric()->minValue(0)->maxValue(365)
                    ->helperText(fn (): string => 'Vacío: usa el plazo por defecto ('.MesaPartesConfig::responseDays().'). Cero: sin plazo propio.'),
                Select::make('default_unit_id')->label('Oficina que suele atenderlo')->searchable()
                    ->options(fn (): array => OrganizationalUnit::query()->active()->orderBy('name')->pluck('name', 'id')->all())
                    ->helperText('Se propone al derivar.'),
                TextInput::make('fee')->label('Derecho de trámite')->numeric()->minValue(0)->prefix('S/')
                    ->helperText('Vacío si es gratuito.'),
                Toggle::make('requires_payment_proof')->label('Pedir comprobante de pago')->inline(false),
                Toggle::make('is_active')->label('Disponible para la ciudadanía')->default(true)->inline(false),
                TextInput::make('sort_order')->label('Orden')->numeric()->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label('Tipo de trámite')->weight('semibold')->searchable()->wrap()
                    ->description(fn (ProcedureType $record): ?string => $record->description),
                TextColumn::make('response_days')->label('Plazo')
                    ->state(fn (ProcedureType $record): string => $record->attentionDays() ? $record->attentionDays().' días hábiles' : 'Sin plazo propio')
                    ->description(fn (ProcedureType $record): ?string => $record->response_days === null ? 'Por defecto' : null),
                TextColumn::make('fee')->label('Derecho')->money('PEN')->placeholder('Gratuito'),
                TextColumn::make('submissions_count')->label('Expedientes')->counts('submissions')->badge()->color('gray')->alignCenter(),
                ToggleColumn::make('is_active')->label('Disponible'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (ProcedureType $record): bool => $record->submissions()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProcedureTypes::route('/'),
        ];
    }
}
