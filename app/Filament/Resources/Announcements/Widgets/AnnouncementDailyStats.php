<?php

namespace App\Filament\Resources\Announcements\Widgets;

use App\Models\Announcement;
use App\Models\AnnouncementStat;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Day-by-day figures of one announcement, newest first: the exact numbers behind the chart.
 */
class AnnouncementDailyStats extends TableWidget
{
    public ?Model $record = null;

    /**
     * Checked again on every Livewire request: the record passed in is not re-authorized by Filament.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Announcement::class) ?? false;
    }

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Detalle por día')
            ->description('Solo aparecen los días en que el anuncio se mostró.')
            ->query(fn (): Builder => AnnouncementStat::query()
                ->when($this->record instanceof Announcement, fn (Builder $query) => $query->whereBelongsTo($this->record), fn (Builder $query) => $query->whereRaw('1 = 0')))
            ->defaultSort('date', 'desc')
            ->paginated([10, 30, 90])
            ->defaultPaginationPageOption(10)
            ->striped()
            ->emptyStateIcon(Heroicon::OutlinedChartBar)
            ->emptyStateHeading('Aún no hay registros')
            ->emptyStateDescription('Las cifras aparecen en cuanto el anuncio se muestre a un visitante del portal.')
            ->columns([
                TextColumn::make('date')
                    ->label('Día')
                    ->date('d/m/Y')
                    ->description(fn (AnnouncementStat $record): string => ucfirst($record->date->translatedFormat('l')))
                    ->sortable(),
                TextColumn::make('views')->label('Vistas')->numeric()->alignEnd()->sortable(),
                TextColumn::make('clicks')->label('Clics')->numeric()->alignEnd()->sortable(),
                TextColumn::make('click_rate')
                    ->label('Tasa de clic')
                    ->state(fn (AnnouncementStat $record): string => AnnouncementStat::formatRate(AnnouncementStat::rate($record->clicks, $record->views)))
                    ->alignEnd(),
                TextColumn::make('dismissals')->label('Cerrados sin entrar')->numeric()->alignEnd()->sortable(),
            ]);
    }
}
