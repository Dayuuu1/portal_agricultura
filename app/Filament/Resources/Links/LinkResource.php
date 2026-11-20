<?php

namespace App\Filament\Resources\Links;

use App\Enums\LinkGroup;
use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Links\Pages\ManageLinks;
use App\Models\Link;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class LinkResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = Link::class;

    protected static ?string $slug = 'enlaces';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|UnitEnum|null $navigationGroup = 'Institucional';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'enlace';

    protected static ?string $pluralModelLabel = 'enlaces de interés';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'md' => 5])->schema([
                    Section::make('Institución')
                        ->columnSpan(['md' => 3])
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Nombre o sigla')
                                ->placeholder('SENASA')
                                ->required()
                                ->maxLength(255),
                            Select::make('group')
                                ->label('Grupo')
                                ->helperText('Define la columna del pie de página donde aparece.')
                                ->options(LinkGroup::class)
                                ->default(LinkGroup::EnlacesInteres)
                                ->required(),
                            TextInput::make('url')
                                ->label('Dirección web')
                                ->placeholder('https://www.senasa.gob.pe')
                                ->url()
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            TextInput::make('description')
                                ->label('Nombre completo / descripción')
                                ->placeholder('Servicio Nacional de Sanidad Agraria')
                                ->helperText('Se muestra al pasar el cursor y lo leen los lectores de pantalla.')
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ]),
                    Section::make('Logo y visibilidad')
                        ->columnSpan(['md' => 2])
                        ->schema([
                            FileUpload::make('logo')
                                ->label('Logo')
                                ->image()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->imageEditor()
                                ->disk('public')
                                ->directory('enlaces')
                                ->maxSize(1024)
                                ->helperText('Imagen horizontal (por ejemplo 300 × 90 px) en PNG, JPG o WEBP, con fondo blanco o transparente. Máximo 1 MB. Sin logo se muestra el nombre.'),
                            Toggle::make('show_in_carousel')
                                ->label('Mostrar en el carrusel de la portada')
                                ->helperText('Los logos pasan en bucle en la sección «Enlaces de interés» de la página de inicio.')
                                ->default(true),
                            Toggle::make('is_active')
                                ->label('Activo')
                                ->helperText('Si lo desactiva, deja de mostrarse en todo el portal.')
                                ->default(true),
                            TextInput::make('sort_order')
                                ->label('Orden')
                                ->helperText('También puede ordenarlos arrastrando en el listado.')
                                ->numeric()
                                ->default(fn (): int => (int) Link::query()->max('sort_order') + 1),
                        ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->disk('public')
                    ->height(40)
                    ->width(140)
                    ->extraImgAttributes(['class' => 'object-contain bg-white rounded'])
                    ->placeholder('Sin logo'),
                TextColumn::make('name')->label('Nombre')->searchable()->weight('semibold')
                    ->description(fn (Link $record): ?string => $record->description),
                TextColumn::make('group')->label('Grupo')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('url')->label('Dirección')->limit(40)->color('gray')->toggleable(isToggledHiddenByDefault: true)
                    ->url(fn (Link $record): string => $record->url, shouldOpenInNewTab: true),
                ToggleColumn::make('show_in_carousel')->label('En carrusel')->alignCenter(),
                ToggleColumn::make('is_active')->label('Activo')->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('group')->label('Grupo')->options(LinkGroup::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('addToCarousel')
                        ->label('Mostrar en el carrusel')
                        ->icon(Heroicon::OutlinedPlayCircle)
                        ->authorize('update')
                        ->action(fn (Collection $records) => $records->each->update(['show_in_carousel' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('removeFromCarousel')
                        ->label('Quitar del carrusel')
                        ->icon(Heroicon::OutlinedStopCircle)
                        ->authorize('update')
                        ->action(fn (Collection $records) => $records->each->update(['show_in_carousel' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLinks::route('/'),
        ];
    }
}
