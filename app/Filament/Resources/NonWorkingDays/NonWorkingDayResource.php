<?php

namespace App\Filament\Resources\NonWorkingDays;

use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\NonWorkingDays\Pages\ManageNonWorkingDays;
use App\Models\NonWorkingDay;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Days off decreed for the public sector. National holidays and Holy Week are already counted; this list adds
 * the rest so terms (Mesa de Partes, Libro de Reclamaciones) skip them.
 */
class NonWorkingDayResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = NonWorkingDay::class;

    protected static ?string $slug = 'dias-no-laborables';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Mesa de partes';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'día no laborable';

    protected static ?string $pluralModelLabel = 'días no laborables';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')->label('Fecha')->required()->unique(ignoreRecord: true),
                TextInput::make('description')->label('Motivo o norma')->required()->maxLength(255)
                    ->placeholder('Ej.: Día no laborable para el sector público (D.S. N° …-PCM)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->label('Fecha')->date('l j \d\e F \d\e Y')->weight('semibold')->sortable(),
                TextColumn::make('description')->label('Motivo o norma')->wrap(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageNonWorkingDays::route('/'),
        ];
    }
}
