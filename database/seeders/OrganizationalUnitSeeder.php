<?php

namespace Database\Seeders;

use App\Enums\UnitType;
use App\Models\OrganizationalUnit;
use Illuminate\Database\Seeder;

class OrganizationalUnitSeeder extends Seeder
{
    public function run(): void
    {
        $direccion = $this->unit(null, 'Dirección Regional', 'direccion-regional', UnitType::AltaDireccion, 'DR',
            'Máxima autoridad de la entidad. Dirige, coordina y supervisa la política agraria regional en concordancia con la política nacional del sector.',
            'Director(a) Regional');

        $tree = [
            ['Oficina de Control Institucional', 'OCI', UnitType::Control, 'Órgano encargado de ejecutar el control gubernamental en la entidad, de conformidad con las normas del Sistema Nacional de Control.', []],
            ['Oficina de Planeamiento y Presupuesto', 'OPP', UnitType::Asesoramiento, 'Conduce los procesos de planeamiento estratégico y operativo, presupuesto, inversiones y modernización de la gestión.', [
                'Unidad de Política Agraria', 'Unidad de Presupuesto', 'Unidad de Inversiones', 'Unidad de Desarrollo Organizacional y de Procesos',
            ]],
            ['Oficina de Asesoría Jurídica', 'OAJ', UnitType::Asesoramiento, 'Brinda asesoramiento legal a la Alta Dirección y a los órganos de la entidad.', []],
            ['Oficina de Administración', 'OA', UnitType::Apoyo, 'Gestiona los sistemas administrativos de contabilidad, tesorería, recursos humanos, abastecimiento y trámite documentario.', [
                'Unidad de Contabilidad', 'Unidad de Tesorería', 'Unidad de Personal', 'Unidad de Abastecimiento y Patrimonio', 'Unidad de Trámite Documentario y Archivo',
            ]],
            ['Oficina de Estadística Agraria e Informática', 'OEAI', UnitType::Apoyo, 'Produce y difunde la estadística agraria regional y administra los sistemas de información de la entidad.', [
                'Unidad de Estadística', 'Unidad de Análisis y Difusión', 'Unidad de Informática y Sistemas',
            ]],
            ['Dirección de Competitividad Agraria', 'DCA', UnitType::Linea, 'Promueve la competitividad de las cadenas productivas, la asociatividad y la articulación de los productores a los mercados.', [
                'Unidad de Información Agraria', 'Unidad de Promoción de la Competitividad',
            ]],
            ['Dirección de Saneamiento Físico Legal de la Propiedad Rural', 'DSFLPR', UnitType::Linea, 'Conduce el saneamiento físico legal y la formalización de predios rústicos y tierras de comunidades nativas.', [
                'Unidad de Predios Agrícolas', 'Unidad de Comunidades Nativas', 'Unidad de Catastro Agrícola',
            ]],
        ];

        foreach ($tree as $order => [$name, $acronym, $type, $description, $children]) {
            $office = $this->unit($direccion, $name, null, $type, $acronym, $description, $type === UnitType::Linea ? 'Director(a)' : 'Jefe(a) de Oficina', $order + 1);

            foreach ($children as $childOrder => $childName) {
                $this->unit($office, $childName, null, UnitType::Unidad, null, null, 'Responsable', $childOrder + 1);
            }
        }
    }

    private function unit(
        ?OrganizationalUnit $parent,
        string $name,
        ?string $slug,
        UnitType $type,
        ?string $acronym,
        ?string $description,
        string $headTitle,
        int $order = 0,
    ): OrganizationalUnit {
        return OrganizationalUnit::query()->updateOrCreate(
            ['name' => $name],
            [
                'parent_id' => $parent?->id,
                'slug' => $slug ?? OrganizationalUnit::query()->where('name', $name)->value('slug'),
                'type' => $type,
                'acronym' => $acronym,
                'description' => $description,
                'head_title' => $headTitle,
                'sort_order' => $order,
                'is_active' => true,
            ],
        );
    }
}
