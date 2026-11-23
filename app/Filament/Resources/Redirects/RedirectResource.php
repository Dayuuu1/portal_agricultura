<?php

namespace App\Filament\Resources\Redirects;

use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Models\Redirect;
use App\Rules\SafeLink;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RedirectResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = Redirect::class;

    protected static ?string $slug = 'redirecciones';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowRightOnRectangle;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Redirecciones';

    protected static ?string $modelLabel = 'redirección';

    protected static ?string $pluralModelLabel = 'redirecciones';

    protected static ?string $recordTitleAttribute = 'old_path';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('old_path')
                    ->label('Dirección antigua')
                    ->helperText('Ruta del portal anterior, por ejemplo /convocatorias-2019 o /?ddownload=1234.')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (string $state): string => str_starts_with($state, '/?') ? $state : Redirect::normalizePath($state)),
                TextInput::make('new_path')
                    ->label('Dirección nueva')
                    ->helperText('Ruta interna (/noticias/…) o dirección completa (https://…).')
                    ->required()
                    ->maxLength(255)
                    ->rule(new SafeLink),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->columns([
                TextColumn::make('old_path')->label('Antigua')->searchable()->limit(60)->copyable(),
                TextColumn::make('new_path')->label('Nueva')->searchable()->limit(60)->color('gray')
                    ->url(fn (Redirect $record): ?string => SafeLink::isSafe($record->new_path) ? $record->new_path : null, shouldOpenInNewTab: true),
                TextColumn::make('hits')->label('Visitas')->numeric()->sortable(),
                TextColumn::make('last_hit_at')->label('Última visita')->since()->placeholder('Nunca')->sortable(),
            ])
            ->filters([
                Filter::make('used')->label('Solo las usadas')->query(fn (Builder $query): Builder => $query->where('hits', '>', 0)),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRedirects::route('/'),
        ];
    }
}
