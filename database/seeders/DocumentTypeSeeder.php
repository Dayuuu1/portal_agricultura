<?php

namespace Database\Seeders;

use App\Enums\DocumentSection;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public const TYPES = [
        ['Resoluciones Directorales', DocumentSection::Normativa, 'Resoluciones emitidas por la Dirección Regional y sus órganos de línea.'],
        ['Saneamiento Físico Legal', DocumentSection::Normativa, 'Padrones de beneficiarios, rectificaciones y actos de saneamiento de la propiedad rural.'],
        ['Documentos de Gestión', DocumentSection::Gestion, 'TUPA, ROF, MAPRO y demás instrumentos de gestión institucional.'],
        ['Planeamiento y Presupuesto', DocumentSection::Gestion, 'Plan Operativo Institucional (POI), evaluaciones y reportes de seguimiento.'],
        ['Cuadro Multianual de Necesidades', DocumentSection::Gestion, 'Aprobación y modificaciones del Cuadro Multianual de Necesidades (CMN).'],
        ['Control Institucional', DocumentSection::Gestion, 'Planes de acción e informes vinculados al Sistema Nacional de Control.'],
        ['Integridad y Rendición de Cuentas', DocumentSection::Gestion, 'Compromisos de integridad e informes de rendición de cuentas.'],
        ['Otros documentos', DocumentSection::Gestion, 'Planes, comunicados y otros documentos institucionales.'],
        ['Estadística Agrícola', DocumentSection::InformacionAgraria, 'Siembras, cosechas, producción, rendimiento y precios en chacra de los principales cultivos.'],
        ['Estadística Pecuaria', DocumentSection::InformacionAgraria, 'Producción pecuaria y beneficio de ganado en la región.'],
        ['Información Agroindustrial', DocumentSection::InformacionAgraria, 'Producción de molinos de arroz y otras agroindustrias.'],
        ['Estadística Complementaria', DocumentSection::InformacionAgraria, 'Precios de fertilizantes, insumos, costo de tractor hora y jornal.'],
        ['Boletines Estadísticos', DocumentSection::InformacionAgraria, 'Boletines mensuales y trimestrales de estadística agraria regional.'],
        ['Precios de Mercados', DocumentSection::InformacionAgraria, 'Precios al por menor en los mercados de Madre de Dios.'],
    ];

    /**
     * Periodic publications are shown on the portal separated by year.
     */
    public const GROUPED_BY_YEAR = ['Resoluciones Directorales', 'Planeamiento y Presupuesto', 'Cuadro Multianual de Necesidades'];

    public function run(): void
    {
        foreach (self::TYPES as $order => [$name, $section, $description]) {
            DocumentType::query()->updateOrCreate(
                ['name' => $name],
                [
                    'section' => $section,
                    'description' => $description,
                    'sort_order' => $order,
                    'group_by_year' => $section === DocumentSection::InformacionAgraria || in_array($name, self::GROUPED_BY_YEAR, true),
                ],
            );
        }
    }
}
