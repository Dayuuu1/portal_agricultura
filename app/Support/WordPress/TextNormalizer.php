<?php

namespace App\Support\WordPress;

/**
 * Cleans titles from the former site: Cyrillic look-alike letters, frequent typos
 * and ALL-CAPS headlines converted to sentence case.
 */
final class TextNormalizer
{
    private const HOMOGLYPHS = [
        'А' => 'A', 'В' => 'B', 'Е' => 'E', 'К' => 'K', 'М' => 'M', 'Н' => 'H', 'О' => 'O', 'Р' => 'P',
        'С' => 'C', 'Т' => 'T', 'Х' => 'X', 'а' => 'a', 'е' => 'e', 'о' => 'o', 'р' => 'p', 'с' => 'c',
        'х' => 'x', 'у' => 'y', 'і' => 'i',
    ];

    private const TYPOS = [
        'necesidadesde' => 'necesidades de',
        'aprovacion' => 'aprobacion',
        'probacion' => 'aprobacion',
        'agropecurio' => 'agropecuario',
        'desiganacion' => 'designacion',
        'regiónal' => 'regional',
        'ftzacarrald' => 'fitzcarrald',
        'afredo' => 'alfredo',
        'hidaldo' => 'hidalgo',
        'buesqueda' => 'búsqueda',
        'coopoazu' => 'copoazú',
    ];

    private const ABBREVIATIONS = [
        'mg.' => 'Mg.', 'ing.' => 'Ing.', 'dr.' => 'Dr.', 'eco.' => 'Eco.', 'econ.' => 'Econ.', 'lic.' => 'Lic.',
        'abog.' => 'Abog.', 'arq.' => 'Arq.', 'sr.' => 'Sr.', 'sra.' => 'Sra.', 'mvz.' => 'MVZ.', 'blgo.' => 'Blgo.',
    ];

    private const ACRONYMS = [
        'GOREMAD', 'GORE', 'DRA', 'DRAMDD', 'DRDAR', 'DRSA', 'GRDE', 'DSFLPR', 'MIDAGRI', 'MINAGRI', 'SENASA', 'SERFOR',
        'SERNANP', 'OSINFOR', 'INIA', 'ANA', 'AGRORURAL', 'AGROIDEAS', 'AGROBANCO', 'FONCODES', 'CEPLAN', 'PCM', 'MEF',
        'INEI', 'OCI', 'OEAI', 'OPP', 'MDD', 'CAS', 'POI', 'PEI', 'CMN', 'ROF', 'TUPA', 'MAPRO', 'MOF', 'CAP', 'PAP',
        'PIP', 'RD', 'RDR', 'SFL', 'MEPA', 'COVID', 'COVID-19', 'ONG', 'SIAF', 'SIGA', 'SUNARP', 'COFOPRI', 'PSI', 'PNIA',
        'FAO', 'ENA', 'CENAGRO', 'DNI', 'RUC', 'ANP', 'PDF', 'TM', 'UIT', 'EIRL', 'SAC', 'SRL', 'MYPE', 'MYPES', 'DS', 'DL',
        'II', 'III', 'IV', 'VI', 'VII', 'VIII', 'IX', 'XI', 'XII',
    ];

    private const PROPER_NOUNS = [
        'Dirección Regional de Desarrollo Agropecuario y Riego', 'Dirección Regional de Agricultura', 'Dirección Regional Agraria',
        'Ministerio de Desarrollo Agrario y Riego', 'Ministerio de Agricultura', 'Contraloría General de la República',
        'Gobierno Regional', 'Cuadro Multianual de Necesidades', 'Cuadro Anual de Necesidades', 'Plan Operativo Institucional',
        'Seguro Agrario Catastrófico', 'Agencias Agrarias', 'Agencia Agraria', 'Resolución Directoral Regional', 'Resolución Directoral',
        'Madre de Dios', 'Puerto Maldonado', 'Padre Aldamiz', 'Campo Ferial', 'Reino Unido', 'Las Piedras', 'Boca Colorado',
        'Tambopata', 'Tahuamanu', 'Manu', 'Inambari', 'Laberinto', 'Iberia', 'Iñapari', 'Huepetuhe', 'Mazuko', 'Planchón',
        'Fitzcarrald', 'Perú', 'Lima', 'Cusco', 'Puno', 'Brasil', 'Bolivia', 'Rusia', 'Navidad',
        'Congreso de la República', 'Reserva Nacional Tambopata', 'Día Mundial de la Alimentación', 'Día Mundial del Cacao',
        'Unión Europea', 'Estados Unidos', 'San Martín', 'Santa Rita', 'Puerto Lucerna', 'Aguas Negras', 'San Jorge', 'La Cachuela',
        'Infierno', 'Salvación', 'Shintuya', 'Pillcopata', 'Quincemil', 'Alerta', 'Mavila', 'Luis Hidalgo', 'Alex Lozano',
        'Alfredo Herrera', 'Carlos Gutiérrez', 'Percy Rojas', 'Agroperú', 'Procompite', 'COREMYPE',
    ];

    private const ACCENTS = [
        'resolucion' => 'resolución', 'direccion' => 'dirección', 'aprobacion' => 'aprobación', 'informacion' => 'información',
        'produccion' => 'producción', 'evaluacion' => 'evaluación', 'implementacion' => 'implementación', 'rendicion' => 'rendición',
        'remision' => 'remisión', 'region' => 'región', 'agricola' => 'agrícola', 'agricolas' => 'agrícolas', 'estadistica' => 'estadística',
        'estadisticas' => 'estadísticas', 'modificacion' => 'modificación', 'modificaciones' => 'modificaciones', 'consolidacion' => 'consolidación',
        'accion' => 'acción', 'rectificacion' => 'rectificación', 'perimetricas' => 'perimétricas', 'fisico' => 'físico', 'tecnico' => 'técnico',
        'tecnica' => 'técnica', 'economico' => 'económico', 'capacitacion' => 'capacitación', 'organizacion' => 'organización',
        'asociacion' => 'asociación', 'gestion' => 'gestión', 'comision' => 'comisión', 'atencion' => 'atención', 'participacion' => 'participación',
        'vacunacion' => 'vacunación', 'sintomatico' => 'sintomático', 'catastrofico' => 'catastrófico', 'ganaderia' => 'ganadería',
        'copoazu' => 'copoazú', 'dia' => 'día', 'segun' => 'según', 'titulacion' => 'titulación', 'inscripcion' => 'inscripción',
        'institucion' => 'institución', 'publicacion' => 'publicación', 'elaboracion' => 'elaboración', 'ejecucion' => 'ejecución',
        'comunicacion' => 'comunicación', 'situacion' => 'situación', 'numero' => 'número', 'credito' => 'crédito', 'tecnologia' => 'tecnología',
        'analisis' => 'análisis', 'practicas' => 'prácticas', 'designacion' => 'designación', 'juridica' => 'jurídica', 'tramite' => 'trámite',
        'logistica' => 'logística', 'caracteristicas' => 'características', 'publico' => 'público', 'publica' => 'pública',
        'electronica' => 'electrónica', 'convocatoria' => 'convocatoria', 'basica' => 'básica', 'rio' => 'río', 'cafe' => 'café',
        'piña' => 'piña', 'agronomico' => 'agronómico', 'ultimo' => 'último', 'unico' => 'único', 'metodologia' => 'metodología',
        'politica' => 'política', 'campaña' => 'campaña', 'diagnostico' => 'diagnóstico', 'reunion' => 'reunión', 'inauguracion' => 'inauguración',
        'clausura' => 'clausura', 'proteccion' => 'protección', 'certificacion' => 'certificación', 'formalizacion' => 'formalización',
        'adjudicacion' => 'adjudicación', 'licitacion' => 'licitación', 'contratacion' => 'contratación', 'seleccion' => 'selección',
        'operacion' => 'operación', 'poblacion' => 'población', 'nutricion' => 'nutrición', 'alimentacion' => 'alimentación',
        'proximo' => 'próximo', 'proxima' => 'próxima', 'beneficas' => 'benéficas', 'citricos' => 'cítricos', 'organicos' => 'orgánicos',
        'organica' => 'orgánica', 'organico' => 'orgánico', 'tecnicos' => 'técnicos', 'edicion' => 'edición', 'areas' => 'áreas',
        'rusticos' => 'rústicos', 'rustico' => 'rústico', 'titulos' => 'títulos', 'vehiculos' => 'vehículos', 'maquinas' => 'máquinas',
        'energia' => 'energía', 'tambien' => 'también', 'despues' => 'después', 'ademas' => 'además', 'hectareas' => 'hectáreas',
        'quimicos' => 'químicos', 'quimica' => 'química', 'biologico' => 'biológico', 'geografica' => 'geográfica',
    ];

    public function title(string $title): string
    {
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = $this->fixHomoglyphs($title);
        $title = (string) preg_replace('/\s+/u', ' ', $title);
        $title = (string) preg_replace('/:(?=\p{L})/u', ': ', $title);
        $title = $this->fixTypos($title);
        $title = (string) preg_replace('/(?<!\p{L})(\p{L}+)\s+\1(?!\p{L})/iu', '$1', $title);
        $title = trim($title, " |·•*\t");
        $title = (string) preg_replace('/(?<!\.)\.$/u', '', $title);

        if ($this->isMostlyUppercase($title)) {
            $title = $this->sentenceCase($title);
        }

        return trim($title);
    }

    public function fixHomoglyphs(string $text): string
    {
        return (string) preg_replace_callback('/\p{L}+/u', function (array $match): string {
            $word = $match[0];

            return preg_match('/\p{Latin}/u', $word) && preg_match('/\p{Cyrillic}/u', $word)
                ? strtr($word, self::HOMOGLYPHS)
                : $word;
        }, $text);
    }

    public function hasHomoglyphs(string $text): bool
    {
        return $this->fixHomoglyphs($text) !== $text;
    }

    private function fixTypos(string $text): string
    {
        foreach (self::TYPOS as $wrong => $right) {
            $text = (string) preg_replace_callback(
                '/(?<!\p{L})'.preg_quote($wrong, '/').'(?!\p{L})/iu',
                fn (array $match): string => $this->matchCase($match[0], $right),
                $text,
            );
        }

        return $text;
    }

    private function isMostlyUppercase(string $text): bool
    {
        $letters = (string) preg_replace('/[^\p{L}]/u', '', $text);

        if (mb_strlen($letters) < 8) {
            return false;
        }

        $uppercase = (string) preg_replace('/[^\p{Lu}]/u', '', $letters);

        return mb_strlen($uppercase) / mb_strlen($letters) > 0.7;
    }

    private function sentenceCase(string $text): string
    {
        $tokens = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

        foreach ($tokens as $index => $token) {
            $core = (string) preg_replace('/^[^\p{L}\p{N}]+|[^\p{L}\p{N}]+$/u', '', $token);

            if ($core === '' || preg_match('/\d|\/|^N[°º]/u', $token) || in_array(mb_strtoupper($core), self::ACRONYMS, true)) {
                continue;
            }

            if (isset(self::ABBREVIATIONS[mb_strtolower($token)])) {
                $tokens[$index] = self::ABBREVIATIONS[mb_strtolower($token)];

                continue;
            }

            $lower = mb_strtolower($token);
            $lowerCore = mb_strtolower($core);
            $tokens[$index] = isset(self::ACCENTS[$lowerCore])
                ? str_replace($lowerCore, self::ACCENTS[$lowerCore], $lower)
                : $lower;
        }

        $text = implode('', $tokens);

        foreach (self::PROPER_NOUNS as $noun) {
            $text = (string) preg_replace('/(?<!\p{L})'.$this->accentInsensitivePattern($noun).'(?!\p{L})/iu', $noun, $text);
        }

        return (string) preg_replace_callback('/^([^\p{L}\p{N}]*)(\p{L})/u', fn (array $match): string => $match[1].mb_strtoupper($match[2]), $text, 1);
    }

    private function accentInsensitivePattern(string $phrase): string
    {
        $pattern = preg_quote(mb_strtolower($phrase), '/');

        return strtr($pattern, [
            'a' => '[aá]', 'á' => '[aá]', 'e' => '[eé]', 'é' => '[eé]', 'i' => '[ií]', 'í' => '[ií]',
            'o' => '[oó]', 'ó' => '[oó]', 'u' => '[uú]', 'ú' => '[uú]', 'n' => '[nñ]', 'ñ' => '[nñ]',
        ]);
    }

    private function matchCase(string $original, string $replacement): string
    {
        if (mb_strtoupper($original) === $original) {
            return mb_strtoupper($replacement);
        }

        if (mb_strtoupper(mb_substr($original, 0, 1)).mb_substr($original, 1) === $original) {
            return mb_strtoupper(mb_substr($replacement, 0, 1)).mb_substr($replacement, 1);
        }

        return $replacement;
    }
}
