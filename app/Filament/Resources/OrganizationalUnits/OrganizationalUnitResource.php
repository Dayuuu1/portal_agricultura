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
                    ->columnSpan(2)
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
                        Select::make('parent_id')
                            ->label('Depende de')
                            ->relationship(
                                'parent',
                                'name',
                                fn (Builder $query, ?OrganizationalUnit $record) => $query->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey())),
                            )
                            ->searchable()
                            ->preload(),
                        Textarea::make('description')
                            ->label('Presentación')
                            ->rows(3)
                            ->columnSpanFull(),
                        RichEditor::make('functions')
                            ->label('Funciones')
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link', 'undo', 'redo'])
                            ->columnSpanFull(),
                    ]),
                Section::make('Responsable y contacto')
                    ->description('Se publica en el directorio institucional.')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columnSpan(1)
                    ->schema([
                        FileUpload::make('head_photo')
                            ->label('Foto')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->avatar()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('unidades')
                            ->maxSize(2048),
                        TextInput::make('head_name')
                            ->label('Nombre del responsable')
                            ->maxLength(255),
                        TextInput::make('head_title')
                            ->label('Cargo')
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Correo')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Teléfono / anexo')
                            ->maxLength(50),
                        TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label('Visible en el portal')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->striped()
            ->emptyStateIcon(Heroicon::OutlinedBuildingOffice2)
            ->emptyStateHeading('No hay unidades en esta vista')
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(['name', 'acronym'])
                    ->weight('semibold')
                    ->description(fn (OrganizationalUnit $record): string => collect([$record->acronym, $record->parent ? 'Depende de: '.$record->parent->name : null])->filter()->implode(' · '))
                    ->wrap(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color('gray'),
                ImageColumn::make('head_photo')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->imageSize(32),
                TextColumn::make('head_name')
                    ->label('Responsable')
                    ->description(fn (OrganizationalUnit $record): ?string => $record->head_title)
                    ->placeholder('Por actualizar')
                    ->color(fn (?string $state): ?string => $state ? null : 'warning'),
                TextColumn::make('email')
                    ->label('Contacto')
                    ->description(fn (OrganizationalUnit $record): ?string => $record->phone)
                    ->placeholder('—')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
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
                    ->label('Ver')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (OrganizationalUnit $record): string => route('institutional.unit', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
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
