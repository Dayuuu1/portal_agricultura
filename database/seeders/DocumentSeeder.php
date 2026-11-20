<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\OrganizationalUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DocumentSeeder extends Seeder
{
    public const SAMPLE_PDF = 'documentos/documento-de-ejemplo.pdf';

    public function run(): void
    {
        $this->storeSamplePdf();

        $this->call(DocumentTypeSeeder::class);
        $types = DocumentType::query()->get()->keyBy('name');

        $saneamiento = OrganizationalUnit::query()->where('acronym', 'DSFLPR')->first();
        $planeamiento = OrganizationalUnit::query()->where('acronym', 'OPP')->first();
        $administracion = OrganizationalUnit::query()->where('acronym', 'OA')->first();

        $documents = [
            ['Resoluciones Directorales', 'Resolución Directoral N° 022-2026-GOREMAD/GRDE/DRDAR/DSFLPR', 'RD N° 022-2026-GOREMAD/GRDE/DRDAR/DSFLPR', '2026-09-15', $saneamiento],
            ['Resoluciones Directorales', 'Resolución Directoral N° 110-2023-GOREMAD-GRDE-DRA/DSFLPR', 'RD N° 110-2023-GOREMAD-GRDE-DRA/DSFLPR', '2023-06-12', $saneamiento],
            ['Resoluciones Directorales', 'Resolución Directoral N° 931-2022-GOREMAD-GRDE-DRA', 'RD N° 931-2022', '2022-12-02', null],
            ['Resoluciones Directorales', 'Resolución Directoral N° 930-2022-GOREMAD-GRDE-DRA', 'RD N° 930-2022', '2022-12-01', null],
            ['Saneamiento Físico Legal', 'Resolución Directoral de rectificación de áreas, linderos y medidas perimétricas', null, '2025-03-18', $saneamiento],
            ['Saneamiento Físico Legal', 'Beneficiarios del procedimiento de saneamiento físico legal – provincias de Manu y Tahuamanu', null, '2025-08-04', $saneamiento],
            ['Documentos de Gestión', 'Texto Único de Procedimientos Administrativos (TUPA)', 'TUPA', '2023-01-10', null],
            ['Documentos de Gestión', 'Reglamento de Organización y Funciones (ROF)', 'ROF', '2022-05-20', null],
            ['Documentos de Gestión', 'Manual de Procedimientos (MAPRO)', 'MAPRO', '2022-05-20', null],
            ['Planeamiento y Presupuesto', 'Plan Operativo Institucional (POI) 2023', 'POI 2023', '2023-01-31', $planeamiento],
            ['Planeamiento y Presupuesto', 'Reporte de seguimiento del POI modificado 2023 – aplicativo CEPLAN', null, '2023-10-15', $planeamiento],
            ['Planeamiento y Presupuesto', 'Informe de evaluación de implementación del POI 2022', null, '2023-02-28', $planeamiento],
            ['Planeamiento y Presupuesto', 'Informe de evaluación de implementación del POI 2021', null, '2022-02-25', $planeamiento],
            ['Cuadro Multianual de Necesidades', 'Aprobación de modificaciones al Cuadro Multianual de Necesidades – setiembre 2026', null, '2026-09-30', $administracion],
            ['Cuadro Multianual de Necesidades', 'Aprobación de modificaciones al Cuadro Multianual de Necesidades – mayo 2026', null, '2026-05-29', $administracion],
            ['Cuadro Multianual de Necesidades', 'Aprobación de modificaciones al Cuadro Multianual de Necesidades – abril 2026', null, '2026-04-30', $administracion],
            ['Cuadro Multianual de Necesidades', 'Consolidación y aprobación del Cuadro Multianual de Necesidades 2025-2027', null, '2025-01-15', $administracion],
            ['Control Institucional', 'Plan de acción para la implementación de recomendaciones del servicio de control posterior', null, '2025-11-20', null],
            ['Integridad y Rendición de Cuentas', 'Formato modelo del Compromiso de Integridad Institucional', null, '2025-02-10', null],
            ['Integridad y Rendición de Cuentas', 'Constancia de remisión de información para el informe de rendición de cuentas anual', null, '2026-03-31', null],
            ['Boletines Estadísticos', 'Boletín estadístico agrario mensual', null, '2026-08-31', null],
            ['Boletines Estadísticos', 'Boletín informativo trimestral', null, '2026-06-30', null],
            ['Precios de Mercados', 'Precios al por menor – Mercado Padre Aldamiz', null, '2026-09-30', null],
        ];

        foreach ($documents as [$type, $title, $code, $issuedAt, $unit]) {
            Document::query()->updateOrCreate(
                ['title' => $title],
                [
                    'document_type_id' => $types[$type]->id,
                    'organizational_unit_id' => $unit?->id,
                    'code' => $code,
                    'issued_at' => $issuedAt,
                    'file_path' => self::SAMPLE_PDF,
                    'file_name' => 'documento-de-ejemplo.pdf',
                    'is_published' => true,
                ],
            );
        }
    }

    /**
     * Writes a minimal one-page PDF so demo downloads work without real files.
     */
    private function storeSamplePdf(): void
    {
        $disk = Storage::disk('public');

        if ($disk->exists(self::SAMPLE_PDF)) {
            return;
        }

        $stream = 'BT /F1 18 Tf 72 720 Td (Documento de ejemplo - Portal DRDAR Madre de Dios) Tj ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        $disk->put(self::SAMPLE_PDF, $pdf);
    }
}
