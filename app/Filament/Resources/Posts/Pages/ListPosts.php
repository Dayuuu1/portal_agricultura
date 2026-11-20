<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    public function getSubheading(): string
    {
        return 'Publique y edite las noticias del portal. Una noticia con fecha futura se publica sola en esa fecha; si desmarca «Publicada» queda como borrador.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Publicar noticia'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todas' => Tab::make('Todas')->badge(Post::query()->count()),
            'publicadas' => Tab::make('Publicadas')
                ->modifyQueryUsing(fn (Builder $query) => $query->published())
                ->badge(Post::query()->published()->count())
                ->badgeColor('success'),
            'programadas' => Tab::make('Programadas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_published', true)->where('published_at', '>', now()))
                ->badge(Post::query()->where('is_published', true)->where('published_at', '>', now())->count())
                ->badgeColor('info'),
            'borradores' => Tab::make('Borradores')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_published', false))
                ->badge(Post::query()->where('is_published', false)->count())
                ->badgeColor('gray'),
            'destacadas' => Tab::make('Destacadas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_featured', true)),
            'sin-imagen' => Tab::make('Sin imagen')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('cover_image'))
                ->badge(Post::query()->whereNull('cover_image')->count())
                ->badgeColor('warning'),
        ];
    }
}
