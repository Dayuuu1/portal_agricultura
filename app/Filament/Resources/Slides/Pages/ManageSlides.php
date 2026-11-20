<?php

namespace App\Filament\Resources\Slides\Pages;

use App\Filament\Resources\Slides\SlideResource;
use App\Models\Slide;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ManageSlides extends ManageRecords
{
    protected static string $resource = SlideResource::class;

    public function getSubheading(): string
    {
        return 'Carrusel principal de la portada: cada banner tiene un título, una foto de fondo, una imagen lateral opcional y un botón. Arrastre las filas para cambiar el orden y programe fechas para que aparezcan o se oculten solos.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewOnSite')
                ->label('Ver en la portada')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(route('home'))
                ->openUrlInNewTab(),
            CreateAction::make()
                ->label('Nuevo banner')
                ->modalWidth(Width::SevenExtraLarge),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos')->badge(Slide::query()->count()),
            'visibles' => Tab::make('Visibles ahora')
                ->modifyQueryUsing(fn (Builder $query) => $query->active())
                ->badge(Slide::query()->active()->count())
                ->badgeColor('success'),
            'sin-fondo' => Tab::make('Sin imagen de fondo')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('background_image'))
                ->badge(Slide::query()->whereNull('background_image')->count())
                ->badgeColor('warning'),
        ];
    }
}
