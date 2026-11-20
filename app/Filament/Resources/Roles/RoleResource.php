<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Models\DocumentType;
use App\Models\Role;
use App\Support\Permissions;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class RoleResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = Role::class;

    protected static ?string $slug = 'roles';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Roles y permisos';

    protected static ?string $modelLabel = 'rol';

    protected static ?string $pluralModelLabel = 'roles';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos del rol')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Por ejemplo: Estadística agraria')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),
                        TextInput::make('description')
                            ->label('Descripción')
                            ->placeholder('Para quién es y qué tareas cubre')
                            ->maxLength(255),
                    ]),
                Section::make('Permisos por módulo')
                    ->description('Marque lo que este rol puede hacer en cada sección del panel. Los módulos sin ninguna casilla no aparecerán en el menú. «Crear», «Editar», «Eliminar» y «Publicar» incluyen «Ver»; «Publicar» incluye «Editar».')
                    ->icon(Heroicon::OutlinedKey)
                    ->schema(static::permissionFieldsets()),
                Section::make('Tipos de documento permitidos')
                    ->description('Limite qué documentos puede ver, subir, editar o eliminar este rol. Por ejemplo, la oficina de estadística solo maneja boletines y series agrarias.')
                    ->icon(Heroicon::OutlinedFunnel)
                    ->visible(fn (Get $get): bool => filled($get('permissions.documentos')))
                    ->schema([
                        Select::make('document_type_ids')
                            ->label('Tipos de documento')
                            ->multiple()
                            ->searchable()
                            ->options(fn (): array => static::documentTypeOptions())
                            ->placeholder('Todos los tipos de documento')
                            ->helperText('Déjelo vacío para permitir todos los tipos.'),
                    ]),
            ]);
    }

    /**
     * One fieldset per navigation group, with a checkbox row per module.
     *
     * @return list<Fieldset>
     */
    private static function permissionFieldsets(): array
    {
        return collect(Permissions::MODULES)
            ->groupBy('group', preserveKeys: true)
            ->map(fn ($modules, string $group): Fieldset => Fieldset::make($group)
                ->columns(['default' => 1, 'lg' => 1, '2xl' => 2])
                ->schema($modules->map(fn (array $module, string $key): CheckboxList => CheckboxList::make("permissions.{$key}")
                    ->label($module['label'])
                    ->helperText($module['description'])
                    ->options(Permissions::actionOptions($key))
                    ->columns(['default' => 2, 'md' => 5])
                    ->bulkToggleable()
                    ->live())->values()->all()))
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<int, string>> Grouped by portal section.
     */
    private static function documentTypeOptions(): array
    {
        return DocumentType::query()
            ->orderBy('section')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (DocumentType $type): string => $type->section?->getLabel() ?? 'Otros')
            ->map(fn ($types): array => $types->pluck('name', 'id')->all())
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Rol')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (Role $record): ?string => $record->description)
                    ->wrap(),
                TextColumn::make('modules')
                    ->label('Módulos')
                    ->badge()
                    ->color('gray')
                    ->state(fn (Role $record): array => collect($record->permissions ?? [])
                        ->map(fn (array $actions, string $module): string => (Permissions::MODULES[$module]['label'] ?? $module)
                            .' · '.implode(', ', array_map(fn (string $action): string => Permissions::ACTION_LABELS[$action] ?? $action, $actions)))
                        ->values()
                        ->all())
                    ->listWithLineBreaks()
                    ->limitList(4)
                    ->expandableLimitedList()
                    ->placeholder('Sin permisos'),
                TextColumn::make('document_type_ids')
                    ->label('Tipos de documento')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Todos los tipos' ? 'success' : 'warning')
                    ->state(fn (Role $record): array => match (true) {
                        ! $record->grants('documentos', Permissions::VIEW) => [],
                        $record->document_type_ids === null => ['Todos los tipos'],
                        default => DocumentType::query()->whereKey($record->document_type_ids)->orderBy('name')->pluck('name')->all(),
                    })
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->placeholder('—'),
                TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'primary' : 'gray')
                    ->sortable(),
                TextColumn::make('updated_at')->label('Actualizado')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Duplicar')
                    ->excludeAttributes(['users_count'])
                    ->mutateRecordDataUsing(fn (array $data): array => [...$data, 'name' => $data['name'].' (copia)'])
                    ->schema([TextInput::make('name')->label('Nombre del nuevo rol')->required()->maxLength(100)->unique(Role::class, 'name')])
                    ->successNotificationTitle('Rol duplicado. Ajuste sus permisos antes de asignarlo.'),
                DeleteAction::make()
                    ->modalDescription(fn (Role $record): string => $record->users()->exists()
                        ? "Este rol está asignado a {$record->users()->count()} usuario(s); perderán los permisos que les daba."
                        : '¿Seguro que desea eliminar este rol?'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
