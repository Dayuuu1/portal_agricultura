<?php

namespace Tests\Unit;

use App\Support\WordPress\TextNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TextNormalizerTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function titles(): array
    {
        return [
            'all caps to sentence case keeping acronyms' => [
                'AUTORIDADES DEL MIDAGRI Y GOREMAD VISITARON PLANTA PROCESADORA DE COPOAZU',
                'Autoridades del MIDAGRI y GOREMAD visitaron planta procesadora de copoazú',
            ],
            'document codes stay untouched' => [
                'RESOLUCION DIRECTORAL N° 022-2026-GOREMAD/GRDE/DRDAR/DSFLPR',
                'Resolución Directoral N° 022-2026-GOREMAD/GRDE/DRDAR/DSFLPR',
            ],
            'typos and duplicated words are fixed' => [
                'APROVACION DE MODIFICACIONES AL CUADRO MULTIANUAL DE NECESIDADESDE DE LA DIRECCION REGIONAL',
                'Aprobación de modificaciones al Cuadro Multianual de Necesidades de la dirección regional',
            ],
            'proper nouns and abbreviations' => [
                'REUNION CON EL MG. AFREDO HERRERA EN PUERTO MALDONADO',
                'Reunión con el Mg. Alfredo Herrera en Puerto Maldonado',
            ],
            'leading pipes and trailing period' => [
                '|Capacitan a productores pecuarios.',
                'Capacitan a productores pecuarios',
            ],
            'mixed case titles are kept' => [
                'Feria «A comer pescado» en Tambopata',
                'Feria «A comer pescado» en Tambopata',
            ],
            'leading numbers do not capitalize the next word' => [
                '7 DE JULIO DIA MUNDIAL DEL CACAO',
                '7 de julio Día Mundial del Cacao',
            ],
        ];
    }

    #[DataProvider('titles')]
    public function test_titles_are_normalized(string $original, string $expected): void
    {
        $this->assertSame($expected, (new TextNormalizer)->title($original));
    }

    public function test_cyrillic_lookalike_letters_are_replaced_only_inside_latin_words(): void
    {
        $normalizer = new TextNormalizer;

        $this->assertSame('COMUNICADO', $normalizer->fixHomoglyphs('COMUNICADО'));
        $this->assertTrue($normalizer->hasHomoglyphs('COMUNICADО'));
        $this->assertSame('Курс', $normalizer->fixHomoglyphs('Курс'));
    }
}
