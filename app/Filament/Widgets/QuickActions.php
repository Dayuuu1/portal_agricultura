<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\JobPostings\JobPostingResource;
use App\Filament\Resources\OrganizationalUnits\OrganizationalUnitResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Slides\SlideResource;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

class QuickActions extends Widget
{
    protected string $view = 'filament.widgets.quick-actions';

    protected static ?int $sort = -1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array{actions: list<array{label: string, description: string, url: string, icon: Heroicon}>}
     */
    protected function getViewData(): array
    {
        /** @var User|null $user */
        $user = auth()->user();
        $canPublishPosts = (bool) $user?->hasPermission('noticias.publish');

        $actions = [
            ['noticias.create', $canPublishPosts ? 'Publicar noticia' : 'Redactar noticia',
                $canPublishPosts
                    ? 'Redacte la nota, elija la categoría y suba la foto de portada y la galería.'
                    : 'Redacte la nota y súbala como borrador; un editor la revisará y publicará.',
                PostResource::getUrl('create'), Heroicon::OutlinedNewspaper],
            ['documentos.create', 'Subir documento', 'Resoluciones, TUPA, POI, boletines y otros archivos PDF para el público.', DocumentResource::getUrl('create'), Heroicon::OutlinedDocumentArrowUp],
            ['convocatorias.create', 'Nueva convocatoria', 'Registre un proceso CAS y publique sus bases y resultados por etapa.', JobPostingResource::getUrl('create'), Heroicon::OutlinedBriefcase],
            ['banners.update', 'Cambiar banners', 'Edite los mensajes destacados del carrusel de la portada.', SlideResource::getUrl(), Heroicon::OutlinedPhoto],
            ['unidades.update', 'Actualizar directorio', 'Responsables, cargos, correos y teléfonos de cada oficina.', OrganizationalUnitResource::getUrl(), Heroicon::OutlinedBuildingOffice2],
            ['mensajes.view', 'Responder mensajes', 'Consultas y sugerencias que la ciudadanía envió desde el portal.', ContactMessageResource::getUrl(), Heroicon::OutlinedEnvelope],
        ];

        return [
            'actions' => collect($actions)
                ->filter(fn (array $action): bool => (bool) $user?->hasPermission($action[0]))
                ->map(fn (array $action): array => [
                    'label' => $action[1],
                    'description' => $action[2],
                    'url' => $action[3],
                    'icon' => $action[4],
                ])
                ->values()
                ->all(),
        ];
    }
}
