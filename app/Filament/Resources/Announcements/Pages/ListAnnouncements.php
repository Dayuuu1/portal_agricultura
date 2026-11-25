<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\Announcement;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAnnouncements extends ListRecords
{
    protected static string $resource = AnnouncementResource::class;

    public function getSubheading(): string
    {
        return 'Ventana que aparece al ingresar al portal: comunicados, eventos, afiches o noticias destacadas. Si hay varios vigentes se muestran juntos en una sola ventana, en el orden de la lista (arrastre las filas). Cada visitante la ve según la frecuencia elegida; al editar un anuncio, vuelve a aparecer para todos.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo anuncio'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos')->badge(Announcement::query()->count()),
            'visibles' => Tab::make('Visibles ahora')
                ->modifyQueryUsing(fn (Builder $query) => $query->active())
                ->badge(Announcement::query()->active()->count())
                ->badgeColor('success'),
            'programados' => Tab::make('Programados')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)->where('starts_at', '>', now()))
                ->badge(Announcement::query()->where('is_active', true)->where('starts_at', '>', now())->count())
                ->badgeColor('info'),
            'finalizados' => Tab::make('Finalizados')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('ends_at', '<', now())),
        ];
    }
}
