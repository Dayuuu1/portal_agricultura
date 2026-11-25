<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Area of the public site a page belongs to, worked out from its address.
 */
enum SiteSection: string implements HasLabel
{
    case Home = 'home';
    case News = 'news';
    case Documents = 'documents';
    case AgrarianInformation = 'agrarian';
    case JobPostings = 'jobs';
    case Institutional = 'institutional';
    case Agencies = 'agencies';
    case Transparency = 'transparency';
    case MesaPartes = 'mesa_partes';
    case CitizenService = 'citizen';
    case Search = 'search';
    case Pages = 'pages';
    case Other = 'other';

    /**
     * First segment of the path => section.
     */
    private const PREFIXES = [
        'noticias' => self::News,
        'documentos' => self::Documents,
        'informacion-agraria' => self::AgrarianInformation,
        'convocatorias' => self::JobPostings,
        'institucional' => self::Institutional,
        'agencias-agrarias' => self::Agencies,
        'transparencia' => self::Transparency,
        'mesa-de-partes' => self::MesaPartes,
        'contacto' => self::CitizenService,
        'libro-de-reclamaciones' => self::CitizenService,
        'buscar' => self::Search,
        'p' => self::Pages,
    ];

    public static function fromPath(string $path): self
    {
        $segment = strtok(trim($path, '/'), '/');

        if ($segment === false || $segment === '') {
            return self::Home;
        }

        return self::PREFIXES[$segment] ?? self::Other;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Home => 'Inicio',
            self::News => 'Noticias',
            self::Documents => 'Documentos',
            self::AgrarianInformation => 'Información agraria',
            self::JobPostings => 'Convocatorias',
            self::Institutional => 'Institucional',
            self::Agencies => 'Agencias agrarias',
            self::Transparency => 'Transparencia',
            self::MesaPartes => 'Mesa de Partes',
            self::CitizenService => 'Atención al ciudadano',
            self::Search => 'Buscador',
            self::Pages => 'Páginas informativas',
            self::Other => 'Otras páginas',
        };
    }
}
