<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = Category::class;

    protected static ?string $slug = 'categorias';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'categoría de noticias';

    protected static ?string $pluralModelLabel = 'categorías de noticias';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                TextInput::make('slug')->label('URL amigable')->alphaDash()->maxLength(255)->unique(ignoreRecord: true)
                    ->helperText('Déjelo vacío para generarlo automáticamente.'),
                TextInput::make('description')->label('Descripción')->maxLength(255)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable(),
                TextColumn::make('description')->label('Descripción')->limit(60),
                TextColumn::make('posts_count')->label('Noticias')->counts('posts'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
