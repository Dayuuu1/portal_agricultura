<?php

namespace Tests\Unit;

use App\Support\Documents\TitlePeriod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TitlePeriodTest extends TestCase
{
    /**
     * Real titles from the former portal: [title, fallback year, series, year, month, day].
     *
     * @return array<string, array{string, ?int, string, ?int, ?int, ?int}>
     */
    public static function titles(): array
    {
        return [
            'monthly statistic' => ['Producción de los principales cultivos – julio 2025', 2025, 'Producción de los principales cultivos', 2025, 7, null],
            'month glued to the year' => ['Boletin JULIO2025', 2025, 'Boletin', 2025, 7, null],
            'month joined by a hyphen' => ['Boletin - Octubre-2019', 2019, 'Boletin', 2019, 10, null],
            'month with "de" before the year' => ['Informe mensual de precios de venta de fertilizantes – enero de 2026', null, 'Informe mensual de precios de venta de fertilizantes', 2026, 1, null],
            'daily prices take the year from the record' => ['Precios al minorista de productos agrícola y pecuario fecha 02 de abril', 2020, 'Precios al minorista de productos agrícola y pecuario', 2020, 4, 2],
            'quarter that names its last month' => ['Boletin Tercer Trimestre Setiembre – 2026', 2026, 'Boletin Tercer Trimestre', 2026, 9, null],
            'campaign range has no single month' => ['Siembra de los principales cultivos de la región Madre de Dios, agosto – julio / 2025 – 2026', 2025, 'Siembra de los principales cultivos de la región Madre de Dios', null, null, null],
            'yearly title with units in parentheses' => ['Producción de los principales cultivos (t.) año 2019', 2019, 'Producción de los principales cultivos', null, null, null],
            'yearly title with loose units' => ['Rendimiento de los principales cultivos kgha año 2019', 2019, 'Rendimiento de los principales cultivos', null, null, null],
            'yearly report' => ['Anuario estadístico 2020', 2020, 'Anuario estadístico', null, null, null],
            'file extension left in the title' => ['Producción Pecuaria y Beneficio Ganado – JULIO 2024.pdf', 2024, 'Producción Pecuaria y Beneficio Ganado', 2024, 7, null],
            'document codes keep their numbers' => ['Resolución Directoral Regional N° 021-2021-GRMDD-GRDE-DRA', 2021, 'Resolución Directoral Regional N° 021-2021-GRMDD-GRDE-DRA', null, null, null],
        ];
    }

    #[DataProvider('titles')]
    public function test_it_reads_the_series_and_reporting_period_from_the_title(string $title, ?int $fallbackYear, string $series, ?int $year, ?int $month, ?int $day): void
    {
        $period = TitlePeriod::fromTitle($title, $fallbackYear);

        $this->assertSame($series, $period->seriesName);
        $this->assertSame([$year, $month, $day], [$period->year, $period->month, $period->day]);
    }

    public function test_series_keys_ignore_case_and_accents(): void
    {
        $this->assertSame(
            TitlePeriod::fromTitle('Boletín octubre 2026')->seriesKey(),
            TitlePeriod::fromTitle('BOLETIN - Mayo-2019')->seriesKey(),
        );
    }

    public function test_a_month_without_any_year_is_not_a_period(): void
    {
        $period = TitlePeriod::fromTitle('Precios al minorista fecha 02 de abril');

        $this->assertFalse($period->hasMonth());
        $this->assertSame('Precios al minorista', $period->seriesName);
    }
}
