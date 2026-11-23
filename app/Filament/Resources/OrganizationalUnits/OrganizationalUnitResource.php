<?php

namespace App\Filament\Resources\OrganizationalUnits;

use App\Enums\UnitType;
use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\OrganizationalUnits\Pages\CreateOrganizationalUnit;
use App\Filament\Resources\OrganizationalUnits\Pages\EditOrganizationalUnit;
use App\Filament\Resources\OrganizationalUnits\Pages\ListOrganizationalUnits;
use App\Models\OrganizationalUnit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class OrganizationalUnitResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = OrganizationalUnit::class;

    protected static ?string $slug = 'unidades-organicas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Institucional';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'unidad orgánica';

    protected static ?string $pluralModelLabel = 'unidades orgánicas';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Órgano o unidad')
                    ->description('Cómo aparece en el organigrama y en su ficha pública.')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->columnSpan(['default' => 3, 'lg' => 2])
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('URL amigable')
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        TextInput::make('acronym')
                            ->label('Siglas')
                            ->maxLength(20),
                        Select::make('type')
                            ->label('Tipo de órgano')
                            ->options(UnitType::class)
                            ->required(),
                        self::parentField(),
                        Textarea::make('description')
                            ->label('Presentación')
                            ->helperText('Uno o dos párrafos: qué hace la unidad y a quién atiende. Encabeza su ficha pública.')
                            ->rows(4)
                            ->columnSpanFull(),
                        RichEditor::make('functions')
                            ->label('Funciones')
                            ->helperText('Escriba cada función como un elemento de lista con viñetas.')
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link', 'undo', 'redo'])
                            ->columnSpanFull(),
                    ]),
                Group::make()
                    ->columnSpan(['default' => 3, 'lg' => 1])
                    ->schema([
                        Section::make('Responsable')
                            ->description('Se muestra en la ficha de la unidad y en el directorio. Si no hay un nombre, la ficha no muestra responsable.')
                            ->icon(Heroicon::OutlinedIdentification)
                            ->schema([
                                FileUpload::make('head_photo')
                                    ->label('Foto')
                                    ->helperText('De frente y con fondo claro. Se recorta en cuadrado y se guarda a 500 × 500 px. JPG, PNG o WEBP de hasta 2 MB.')
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->avatar()
                                    ->imageEditor()
                                    ->disk('public')
                                    ->directory(OrganizationalUnit::PHOTO_DIRECTORY)
                                    ->maxSize(2048),
                                TextInput::make('head_name')
                                    ->label('Nombre completo')
                                    ->helperText('Con el grado o título si se usa, por ejemplo «Ing.» o «Abog.».')
                                    ->maxLength(255),
                                TextInput::make('head_title')
                                    ->label('Cargo')
                                    ->placeholder('Jefe(a) de Oficina')
                                    ->maxLength(255),
                            ]),
                        Section::make('Contacto')
                            ->description('Para que el ciudadano llegue a la unidad sin pasar por la central.')
                            ->icon(Heroicon::OutlinedPhone)
                            ->schema([
                                TextInput::make('email')
                                    ->label('Correo')
                                    ->email()
                                    ->maxLength(255),
                                TextInput::make('phone')
                                    ->label('Teléfono')
                                    ->helperText('Directo, o el de la central con su anexo.')
                                    ->maxLength(50),
                            ]),
                        Section::make('Publicación')
                            ->icon(Heroicon::OutlinedEye)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Visible en el portal')
                                    ->helperText('Desactívela para retirar la unidad del organigrama y del directorio sin perder sus datos.')
                                    ->default(true),
                                TextInput::make('sort_order')
                                    ->label('Orden')
                                    ->helperText('Posición entre las unidades del mismo nivel. También puede ordenarlas arrastrando en la lista.')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0),
                            ]),
                    ]),
            ]);
    }

    /**
     * Offers only the bodies under which the unit still fits in the three levels of the organization chart,
     * which also rules out the unit itself and anything below it.
     */
    private static function parentField(): Select
    {
        return Select::make('parent_id')
            ->label('Depende de')
            ->relationship(
                'parent',
                'name',
                function (Builder $query, ?OrganizationalUnit $record): Builder {
                    $levelsBelow = $record?->levelsBelow() ?? 0;

                    return $query
                        ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                        ->when($levelsBelow >= OrganizationalUnit::MAX_DEPTH, fn (Builder $query) => $query->whereRaw('1 = 0'))
                        ->when($levelsBelow === 1, fn (Builder $query) => $query->whereNull('parent_id'))
                        ->when($levelsBelow === 0, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                            ->whereNull('parent_id')
                            ->orWhereHas('parent', fn (Builder $parent) => $parent->whereNull('parent_id'))))
                        ->orderedAsTree();
                },
            )
            ->disabled(fn (?OrganizationalUnit $record): bool => ($record?->levelsBelow() ?? 0) >= OrganizationalUnit::MAX_DEPTH)
            ->helperText(fn (?OrganizationalUnit $record): string => match ($record?->levelsBelow() ?? 0) {
                0 => 'Déjelo vacío solo para el órgano principal de la entidad.',
                1 => 'Tiene unidades a su cargo, así que solo puede depender del órgano principal.',
                default => 'Es el órgano principal del organigrama: no depende de otro.',
            })
            ->searchable()
            ->preload();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderedAsTree())
            ->reorderable('sort_order')
            ->paginated(false)
            ->emptyStateIcon(Heroicon::OutlinedBuildingOffice2)
            ->emptyStateHeading('No hay unidades en esta vista')
            ->columns([
                TextColumn::make('name')
                    ->label('Unidad')
                    ->searchable(['name', 'acronym'])
                    ->weight(fn (OrganizationalUnit $record): string => $record->depth() < 2 ? 'semibold' : 'normal')
                    ->icon(fn (OrganizationalUnit $record): ?Heroicon => $record->depth() === 2 ? Heroicon::OutlinedArrowTurnDownRight : null)
                    ->iconColor('gray')
                    ->description(fn (OrganizationalUnit $record): ?string => $record->acronym)
                    ->extraAttributes(fn (OrganizationalUnit $record): array => ['class' => match ($record->depth()) {
                        0 => '',
                        1 => 'ps-4',
                        default => 'ps-8',
                    }])
                    ->wrap(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('head_photo')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->imageSize(36),
                TextColumn::make('head_name')
                    ->label('Responsable')
                    ->description(fn (OrganizationalUnit $record): ?string => $record->head_title)
                    ->placeholder('Sin designar')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('email')
                    ->label('Contacto')
                    ->description(fn (OrganizationalUnit $record): ?string => $record->phone)
                    ->placeholder('—')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('profile')
                    ->label('Ficha pública')
                    ->state(fn (OrganizationalUnit $record): int => count($record->missingProfileItems()))
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        0 => 'Completa',
                        1 => 'Falta 1 dato',
                        default => "Faltan {$state} datos",
                    })
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'success' : 'warning')
                    ->tooltip(fn (OrganizationalUnit $record): ?string => ($missing = $record->missingProfileItems()) === []
                        ? null
                        : 'Falta: '.Str::ucfirst(collect($missing)->join(', ', ' y ')).'.'),
                IconColumn::make('is_active')
                    ->label('Visible')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(UnitType::class),
                SelectFilter::make('parent')
                    ->label('Depende de')
                    ->relationship('parent', 'name'),
            ])
            ->recordActions([
                Action::make('viewOnSite')
                    ->label('Ver en el portal')
                    ->iconButton()
                    ->tooltip('Ver en el portal')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (OrganizationalUnit $record): string => route('institutional.unit', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (OrganizationalUnit $record): bool => $record->is_active),
                EditAction::make(),
                self::configureDeleteAction(DeleteAction::make()),
            ]);
    }

    /**
     * A unit in use stays listed with a disabled delete button that explains why, instead of vanishing.
     */
    public static function configureDeleteAction(DeleteAction $action): DeleteAction
    {
        return $action
            ->visible(fn (): bool => (bool) auth()->user()?->hasPermission('unidades.delete'))
            ->authorizationTooltip()
            ->authorizationMessage(fn (OrganizationalUnit $record): string => self::deletionBlockedMessage($record));
    }

    public static function deletionBlockedMessage(OrganizationalUnit $unit): string
    {
        $labels = [
            'units' => ['unidad que depende de ella', 'unidades que dependen de ella'],
            'documents' => ['documento', 'documentos'],
            'job_postings' => ['convocatoria', 'convocatorias'],
            'complaints' => ['reclamo', 'reclamos'],
            'procedure_types' => ['tipo de trámite', 'tipos de trámite'],
            'submissions' => ['expediente de mesa de partes', 'expedientes de mesa de partes'],
        ];

        $linked = collect($unit->linkedRecordCounts())
            ->map(fn (int $count, string $kind): string => $count.' '.$labels[$kind][$count === 1 ? 0 : 1]);

        if ($linked->isEmpty()) {
            return 'No puede eliminar esta unidad.';
        }

        return 'Está vinculada a '.$linked->join(', ', ' y ').'. Para retirarla del portal, desactive «Visible en el portal».';
    }

    /**
     * @return Builder<OrganizationalUnit>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('parent');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizationalUnits::route('/'),
            'create' => CreateOrganizationalUnit::route('/create'),
            'edit' => EditOrganizationalUnit::route('/{record}/edit'),
        ];
    }
}
