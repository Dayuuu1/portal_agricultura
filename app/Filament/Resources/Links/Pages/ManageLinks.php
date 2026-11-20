<?php

namespace App\Filament\Resources\Links\Pages;

use App\Enums\LinkGroup;
use App\Filament\Resources\Links\LinkResource;
use App\Models\Link;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ManageLinks extends ManageRecords
{
    protected static string $resource = LinkResource::class;

    public function getSubheading(): string
    {
        return 'Enlaces a otras instituciones. Los marcados «En carrusel» pasan en bucle en la portada; todos aparecen en el pie de página según su grupo. Arrastre las filas para cambiar el orden.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewCarousel')
                ->label('Ver carrusel en la portada')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(route('home').'#titulo-enlaces')
                ->openUrlInNewTab(),
            CreateAction::make()->label('Nuevo enlace'),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'todos' => Tab::make('Todos')->badge(Link::query()->count()),
            'carrusel' => Tab::make('En el carrusel')
                ->icon(Heroicon::OutlinedPlayCircle)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('show_in_carousel', true))
                ->badge(Link::query()->inCarousel()->count()),
            'sin-logo' => Tab::make('Sin logo')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('logo'))
                ->badge(Link::query()->whereNull('logo')->count())
                ->badgeColor('warning'),
        ];

        foreach (LinkGroup::cases() as $group) {
            $tabs[$group->value] = Tab::make($group->getLabel())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('group', $group));
        }

        return $tabs;
    }
}
