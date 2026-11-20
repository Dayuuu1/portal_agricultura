<?php

namespace App\Filament\Resources\Users;

use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Role;
use App\Models\User;
use App\Support\Permissions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

class UserResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = User::class;

    protected static ?string $slug = 'usuarios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'usuarios';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nombres y apellidos')->required()->maxLength(255),
                TextInput::make('position')->label('Cargo / oficina')->maxLength(255),
                TextInput::make('email')->label('Correo institucional')->email()->required()->maxLength(255)->unique(ignoreRecord: true)
                    ->autocomplete('off'),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->rule(Password::default())
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation): string => ($operation === 'edit' ? 'Déjela vacía para mantener la actual. ' : '')
                        .'Mínimo 10 caracteres, con mayúsculas, minúsculas y números. La persona podrá cambiarla desde su perfil.'),
                Section::make('Acceso')
                    ->description('Qué puede ver y hacer esta persona en el panel.')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Cuenta activa')
                            ->helperText('Desactive la cuenta cuando la persona deje el cargo, en lugar de eliminarla. Una cuenta inactiva no puede ingresar.')
                            ->default(true)
                            ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false),
                        Toggle::make('is_admin')
                            ->label('Administrador del portal')
                            ->helperText('Acceso total: todos los módulos, roles y usuarios. Asígnelo a la menor cantidad de personas posible.')
                            ->live()
                            ->visible(fn (): bool => (bool) auth()->user()?->is_admin)
                            ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false),
                        CheckboxList::make('roles')
                            ->label('Roles')
                            ->relationship('roles', 'name', fn (Builder $query): Builder => $query->whereKey(static::assignableRoleIds()))
                            ->descriptions(fn (): array => Role::query()->get()
                                ->mapWithKeys(fn (Role $role): array => [$role->id => $role->description ?: Permissions::summary($role->permissions ?? [])])
                                ->all())
                            ->columns(2)
                            ->columnSpanFull()
                            ->required(fn (Get $get): bool => ! static::makesAdministrator($get))
                            ->validationMessages(['required' => 'Asigne al menos un rol o marque a la persona como administradora.'])
                            ->helperText(fn (): HtmlString|string => auth()->user()?->is_admin
                                ? new HtmlString('Los permisos de cada rol se configuran en <a href="'.e(RoleResource::getUrl()).'" class="font-semibold text-primary-600 underline">Roles y permisos</a>.')
                                : 'Solo puede asignar roles cuyos permisos usted también tiene.')
                            ->hidden(fn (Get $get): bool => static::makesAdministrator($get)),
                    ]),
            ]);
    }

    /**
     * Only an administrator's choice counts: the switch is not even shown to anyone else.
     */
    private static function makesAdministrator(Get $get): bool
    {
        return (bool) auth()->user()?->is_admin && (bool) $get('is_admin');
    }

    /**
     * Roles the current user may hand out without granting more than they have.
     *
     * @return list<int>
     */
    public static function assignableRoleIds(): array
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        return Role::query()->get()
            ->filter(fn (Role $role): bool => $user->is_admin || $role->isCoveredBy($user))
            ->modelKeys();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles'))
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()
                    ->description(fn (User $record): ?string => $record->position),
                TextColumn::make('email')->label('Correo')->searchable(),
                TextColumn::make('access')
                    ->label('Acceso')
                    ->badge()
                    ->state(fn (User $record): array => $record->is_admin
                        ? ['Administrador']
                        : ($record->roles->pluck('name')->sort()->values()->all() ?: ['Sin roles']))
                    ->color(fn (string $state): string => match ($state) {
                        'Administrador' => 'primary',
                        'Sin roles' => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('two_factor')
                    ->label('2 pasos')
                    ->tooltip('Verificación en dos pasos (app de autenticación)')
                    ->boolean()
                    ->state(fn (User $record): bool => filled($record->getAppAuthenticationSecret())),
                IconColumn::make('is_active')->label('Activa')->boolean(),
                TextColumn::make('last_login_at')->label('Último acceso')->since()->placeholder('Nunca')->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')->label('Rol')->relationship('roles', 'name')->multiple()->preload(),
                TernaryFilter::make('is_active')->label('Cuenta activa'),
                TernaryFilter::make('is_admin')->label('Administrador'),
            ])
            ->recordActions([
                Action::make('activity')
                    ->label('Actividad')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->color('gray')
                    ->visible(fn (): bool => (bool) auth()->user()?->hasPermission('actividad.view'))
                    ->url(fn (User $record): string => route('filament.admin.resources.registro-de-actividad.index', [
                        'filters' => ['user' => ['value' => $record->id]],
                    ])),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
