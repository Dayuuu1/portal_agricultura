<?php

namespace Database\Seeders;

use App\Enums\DocumentSection;
use App\Models\DocumentType;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Starting roles for the usual jobs at the DRA. Existing roles are left untouched,
 * so the administrator's later adjustments survive a re-seed.
 */
class RoleSeeder extends Seeder
{
    private const ALL = ['view', 'create', 'update', 'delete', 'publish'];

    private const CRUD = ['view', 'create', 'update', 'delete'];

    public function run(): void
    {
        $agrarianTypeIds = DocumentType::query()
            ->where('section', DocumentSection::InformacionAgraria)
            ->pluck('id')
            ->all();

        $roles = [
            ['Editor de contenidos', 'Imagen institucional: publica noticias, páginas y banners de la portada.', [
                'noticias' => self::ALL, 'categorias' => self::CRUD, 'paginas' => self::ALL, 'banners' => self::CRUD, 'enlaces' => self::CRUD,
            ], null],
            ['Redactor de noticias', 'Redacta notas de prensa como borrador; un editor las revisa y publica.', [
                'noticias' => ['view', 'create', 'update'], 'categorias' => ['view'],
            ], null],
            ['Gestor documental', 'Secretaría y trámite documentario: sube y publica resoluciones y documentos de gestión.', [
                'documentos' => self::ALL, 'tipos_documento' => ['view'],
            ], null],
            ['Estadística agraria', 'Oficina de información agraria: actualiza boletines, series y precios por año.', [
                'documentos' => self::ALL,
            ], $agrarianTypeIds ?: null],
            ['Recursos humanos', 'Publica las convocatorias CAS, sus bases y resultados por etapa.', [
                'convocatorias' => self::ALL,
            ], null],
            ['Institucional', 'Mantiene el directorio, el organigrama y las agencias agrarias.', [
                'unidades' => self::CRUD, 'agencias' => self::CRUD, 'enlaces' => self::CRUD,
            ], null],
            ['Mesa de partes', 'Trámite documentario: revisa los documentos de la Mesa de Partes Virtual, los observa, deriva y registra las respuestas.', [
                'mesa_partes' => ['view', 'update'], 'mesa_partes_config' => ['view'],
            ], null],
            ['Atención al ciudadano', 'Atiende los mensajes de contacto y los reclamos del Libro de Reclamaciones.', [
                'mensajes' => ['view', 'update'], 'reclamaciones' => ['view', 'update'],
            ], null],
        ];

        foreach ($roles as [$name, $description, $permissions, $documentTypeIds]) {
            Role::query()->firstOrCreate(['name' => $name], [
                'description' => $description,
                'permissions' => $permissions,
                'document_type_ids' => $documentTypeIds,
            ]);
        }
    }
}
