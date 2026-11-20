<?php

namespace App\Support\WordPress;

use Illuminate\Support\Str;

/**
 * Infers the document type of a former WordPress download from its title and
 * from the pages or posts that linked to it.
 */
final class DocumentClassifier
{
    public const JOB_POSTING = 'convocatoria';

    private const JOB_POSTING_PATTERN = '/\bCAST?\b|CONVOCATORIA|CONCURSO PUBLICO|CURRICU|ENTREVISTA|POSTULANTE|PRACTICAS (PRE)?PROFESIONALES|BASES DEL (PROCESO|CONCURSO)/';

    /**
     * Ordered rules: the first matching pattern wins.
     */
    private const TITLE_RULES = [
        '/CUADRO (MULTI)?ANUAL|NECESIDADES|\bCMN\b/' => 'Cuadro Multianual de Necesidades',
        '/\bPOI\b|PLAN OPERATIVO|CEPLAN|PRESUPUEST|PLAN ESTRATEGICO|\bPEI\b/' => 'Planeamiento y Presupuesto',
        '/CONTROL POSTERIOR|CONTROL INSTITUCIONAL|PLAN DE ACCION|\bOCI\b|RECOMENDACIONES DEL INFORME/' => 'Control Institucional',
        '/INTEGRIDAD|RENDICION DE CUENTAS|\bETICA\b|ANTISOBORNO|CONFLICTO DE INTERES/' => 'Integridad y Rendición de Cuentas',
        '/\bROF\b|\bTUPA\b|\bMAPRO\b|\bMOF\b|\bCAP\b|\bPAP\b|REGLAMENTO|MANUAL DE|DIRECTIVA|TEXTO UNICO/' => 'Documentos de Gestión',
        '/RESOLUCION|\bRD[_\s-]*\d|\bR\.?\s?E\.?\s?R\.?\s|\bR\.?\s?D\.?\s?R\.?\s/' => 'Resoluciones Directorales',
        '/SANEAMIENTO|BENEFICIARIO|PREDIO|CATASTRO|NATIVA|TITULACION|RECTIFICACION/' => 'Saneamiento Físico Legal',
        '/BOLETIN|ANUARIO/' => 'Boletines Estadísticos',
        '/PRECIO.{0,40}(MERCADO|MINORISTA|CONSUMIDOR|POR MENOR)|MERCADO/' => 'Precios de Mercados',
        '/PECUARI|GANADO|VACUNO|BENEFICIO|LECHE|AVICOLA|PORCINO|CARNE/' => 'Estadística Pecuaria',
        '/MOLINO|AGROINDUSTR|PILADO/' => 'Información Agroindustrial',
        '/FERTILIZANTE|TRACTOR|JORNAL|INSUMO|PLAGUICIDA|ADHERENTE|FOLIAR/' => 'Estadística Complementaria',
        '/PRODUCCION|SIEMBRA|SUPERFICIE|COSECHA|RENDIMIENTO|CHACRA|CULTIVO|AGRICOLA|CENSO|ARROZ/' => 'Estadística Agrícola',
    ];

    private const SOURCE_RULES = [
        '/INFORMACION PECUARIA/' => 'Estadística Pecuaria',
        '/INFORMACION AGROINDUSTRIAL/' => 'Información Agroindustrial',
        '/ESTADISTICA COMPLEMENTARIA|INFORMACION COMPLEMENTARIA/' => 'Estadística Complementaria',
        '/INFORMACION AGRICOLA/' => 'Estadística Agrícola',
        '/BOLETIN/' => 'Boletines Estadísticos',
    ];

    private const UNIT_BY_TYPE = [
        'Cuadro Multianual de Necesidades' => 'OA',
        'Planeamiento y Presupuesto' => 'OPP',
        'Control Institucional' => 'OCI',
        'Saneamiento Físico Legal' => 'DSFLPR',
        'Estadística Agrícola' => 'OEAI',
        'Estadística Pecuaria' => 'OEAI',
        'Información Agroindustrial' => 'OEAI',
        'Estadística Complementaria' => 'OEAI',
        'Boletines Estadísticos' => 'OEAI',
        'Precios de Mercados' => 'OEAI',
    ];

    /**
     * @param  list<string>  $sourceTitles  Titles of the pages/posts that linked the download.
     * @param  list<string>  $categories
     */
    public function classify(string $title, array $sourceTitles = [], array $categories = []): string
    {
        $text = $this->fold($title);
        $sources = $this->fold(implode(' | ', $sourceTitles));

        if (preg_match(self::JOB_POSTING_PATTERN, $text) || preg_match(self::JOB_POSTING_PATTERN, $sources)) {
            return self::JOB_POSTING;
        }

        if (in_array('DOCUMENTOS DE GESTION', array_map($this->fold(...), $categories), true)) {
            return 'Documentos de Gestión';
        }

        foreach (self::SOURCE_RULES as $pattern => $type) {
            if (preg_match($pattern, $sources)) {
                return $type;
            }
        }

        foreach (self::TITLE_RULES as $pattern => $type) {
            if (preg_match($pattern, $text)) {
                return $type;
            }
        }

        foreach (self::TITLE_RULES as $pattern => $type) {
            if (preg_match($pattern, $sources)) {
                return $type;
            }
        }

        return 'Otros documentos';
    }

    public function isJobPostingTitle(string $title): bool
    {
        return (bool) preg_match(self::JOB_POSTING_PATTERN, $this->fold($title));
    }

    /**
     * Posts whose only purpose was publishing documents (resolutions, CMN, POI, CAS results…).
     */
    public function isDocumentAnnouncement(string $title): bool
    {
        $text = $this->fold($title);

        if (preg_match(self::JOB_POSTING_PATTERN, $text)) {
            return true;
        }

        foreach (array_keys(array_slice(self::TITLE_RULES, 0, 8, true)) as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    public function unitAcronymFor(string $type, string $title): ?string
    {
        if (preg_match('/DSFLPR|SANEAMIENTO/', $this->fold($title))) {
            return 'DSFLPR';
        }

        return self::UNIT_BY_TYPE[$type] ?? null;
    }

    private function fold(string $text): string
    {
        return Str::upper(Str::ascii($text));
    }
}
