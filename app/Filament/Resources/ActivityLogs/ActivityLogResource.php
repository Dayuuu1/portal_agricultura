<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\ActivityLogs\Pages\ManageActivityLogs;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ActivityLogResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = ActivityLog::class;

    protected static ?string $slug = 'registro-de-actividad';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Registro de actividad';

    protected static ?string $modelLabel = 'actividad';

    protected static ?string $pluralModelLabel = 'registro de actividad';

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s'),
                TextEntry::make('user.name')->label('Usuario')->placeholder('Sistema / visitante'),
                TextEntry::make('event_label')->label('Acción')->badge()->color(fn (ActivityLog $record): string => self::eventColor($record->event)),
                TextEntry::make('subject_type_label')->label('Tipo de registro')->placeholder('—'),
                TextEntry::make('subject_label')->label('Registro')->columnSpanFull()->placeholder('—'),
                TextEntry::make('ip_address')->label('Dirección IP')->placeholder('—'),
                TextEntry::make('changes')
                    ->label('Cambios')
                    ->columnSpanFull()
                    ->state(fn (ActivityLog $record): array => collect($record->changes ?? [])
                        ->map(fn (array $change, string $field): string => sprintf(
                            '%s: «%s» → «%s»',
                            $field,
                            self::stringify($change['old'] ?? null),
                            self::stringify($change['new'] ?? null),
                        ))
                        ->values()
                        ->all())
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->placeholder('Sin detalle de cambios'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('user.name')->label('Usuario')->placeholder('Sistema / visitante')->searchable(),
                TextColumn::make('event_label')
                    ->label('Acción')
                    ->badge()
                    ->color(fn (ActivityLog $record): string => self::eventColor($record->event)),
                TextColumn::make('subject_type_label')->label('Tipo')->placeholder('—'),
                TextColumn::make('subject_label')->label('Registro')->searchable()->limit(60)->wrap(),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')->label('Acción')->options(ActivityLog::EVENT_LABELS),
                SelectFilter::make('user')->label('Usuario')->relationship('user', 'name')->searchable()->preload(),
                SelectFilter::make('subject_type')->label('Tipo de registro')->options(ActivityLog::SUBJECT_LABELS),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageActivityLogs::route('/'),
        ];
    }

    private static function eventColor(string $event): string
    {
        return match ($event) {
            'created' => 'success',
            'updated' => 'info',
            'deleted', 'login_failed' => 'danger',
            'role_assigned', 'role_removed' => 'warning',
            default => 'gray',
        };
    }

    private static function stringify(mixed $value): string
    {
        return match (true) {
            $value === null || $value === '' => 'vacío',
            is_bool($value) => $value ? 'sí' : 'no',
            is_array($value) && ! array_is_list($value) => collect($value)
                ->map(fn (mixed $item, string|int $key): string => $key.': '.self::stringify($item))
                ->implode('; '),
            is_array($value) => implode(', ', array_map(fn ($item) => is_scalar($item) ? (string) $item : self::stringify($item), $value)),
            default => (string) $value,
        };
    }
}
