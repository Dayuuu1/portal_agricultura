<?php

namespace App\Filament\Widgets;

use App\Models\ActivityLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentActivity extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Actividad reciente';

    public static function canView(): bool
    {
        return auth()->user()?->hasPermission('actividad.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ActivityLog::query()->with('user')->latest('created_at')->limit(8))
            ->paginated(false)
            ->columns([
                TextColumn::make('created_at')->label('Fecha')->since(),
                TextColumn::make('user.name')->label('Usuario')->placeholder('Sistema / visitante'),
                TextColumn::make('event_label')->label('Acción')->badge(),
                TextColumn::make('subject_type_label')->label('Tipo')->placeholder('—'),
                TextColumn::make('subject_label')->label('Registro')->limit(60),
            ]);
    }
}
