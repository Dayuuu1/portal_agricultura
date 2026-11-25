<?php

namespace App\Support;

/**
 * Catalog of the admin panel modules and the actions a role can be granted on each one.
 * Permissions are written as "module.action", for example "documentos.publish".
 */
final class Permissions
{
    public const VIEW = 'view';

    public const CREATE = 'create';

    public const UPDATE = 'update';

    public const DELETE = 'delete';

    public const PUBLISH = 'publish';

    public const ACTION_LABELS = [
        self::VIEW => 'Ver',
        self::CREATE => 'Crear',
        self::UPDATE => 'Editar',
        self::DELETE => 'Eliminar',
        self::PUBLISH => 'Publicar',
    ];

    private const CRUD = [self::VIEW, self::CREATE, self::UPDATE, self::DELETE];

    private const CRUD_PUBLISH = [self::VIEW, self::CREATE, self::UPDATE, self::DELETE, self::PUBLISH];

    /**
     * @var array<string, array{label: string, group: string, description: string, actions: list<string>}>
     */
    public const MODULES = [
        'noticias' => ['label' => 'Noticias', 'group' => 'Contenido', 'description' => 'Notas de prensa del portal. Sin «Publicar», lo que se cree queda como borrador para revisión.', 'actions' => self::CRUD_PUBLISH],
        'categorias' => ['label' => 'Categorías de noticias', 'group' => 'Contenido', 'description' => 'Clasificación de las noticias.', 'actions' => self::CRUD],
        'paginas' => ['label' => 'Páginas', 'group' => 'Contenido', 'description' => 'Páginas institucionales de contenido libre.', 'actions' => self::CRUD_PUBLISH],
        'banners' => ['label' => 'Banners de portada', 'group' => 'Contenido', 'description' => 'Carrusel de la página de inicio.', 'actions' => self::CRUD],
        'anuncios' => ['label' => 'Anuncios', 'group' => 'Contenido', 'description' => 'Ventana que aparece al ingresar al portal: comunicados, eventos, afiches o noticias destacadas.', 'actions' => self::CRUD],
        'documentos' => ['label' => 'Documentos', 'group' => 'Documentos', 'description' => 'Resoluciones, documentos de gestión y estadística. Puede limitarse a ciertos tipos de documento.', 'actions' => self::CRUD_PUBLISH],
        'tipos_documento' => ['label' => 'Tipos de documento', 'group' => 'Documentos', 'description' => 'Categorías de documentos y su sección en el portal.', 'actions' => self::CRUD],
        'mesa_partes' => ['label' => 'Mesa de partes: expedientes', 'group' => 'Mesa de partes', 'description' => 'Documentos recibidos por la Mesa de Partes Virtual y sus administrados. «Editar» permite observar, derivar, responder, archivar y suspender cuentas.', 'actions' => [self::VIEW, self::UPDATE]],
        'mesa_partes_config' => ['label' => 'Mesa de partes: configuración', 'group' => 'Mesa de partes', 'description' => 'Horario, plazos, tipos de trámite y días no laborables de la Mesa de Partes Virtual.', 'actions' => self::CRUD],
        'convocatorias' => ['label' => 'Convocatorias', 'group' => 'Convocatorias', 'description' => 'Procesos CAS, bases y resultados.', 'actions' => self::CRUD_PUBLISH],
        'unidades' => ['label' => 'Unidades orgánicas', 'group' => 'Institucional', 'description' => 'Organigrama y directorio.', 'actions' => self::CRUD],
        'agencias' => ['label' => 'Agencias agrarias', 'group' => 'Institucional', 'description' => 'Sedes en provincias.', 'actions' => self::CRUD],
        'enlaces' => ['label' => 'Enlaces de interés', 'group' => 'Institucional', 'description' => 'Enlaces a otras instituciones.', 'actions' => self::CRUD],
        'mensajes' => ['label' => 'Mensajes recibidos', 'group' => 'Atención al ciudadano', 'description' => 'Consultas y sugerencias del formulario de contacto. «Editar» permite marcarlos como leídos.', 'actions' => [self::VIEW, self::UPDATE, self::DELETE]],
        'reclamaciones' => ['label' => 'Libro de reclamaciones', 'group' => 'Atención al ciudadano', 'description' => 'Reclamos de la ciudadanía (DS N° 007-2020-PCM). «Editar» permite cambiar el estado y registrar la respuesta. Los reclamos no se pueden eliminar.', 'actions' => [self::VIEW, self::UPDATE]],
        'datos_portal' => ['label' => 'Datos del portal', 'group' => 'Configuración', 'description' => 'Nombre, contacto, redes y enlaces a servicios.', 'actions' => [self::VIEW, self::UPDATE]],
        'usuarios' => ['label' => 'Usuarios', 'group' => 'Configuración', 'description' => 'Crear cuentas y asignar roles. Nunca permite crear administradores ni dar permisos que la persona no tenga.', 'actions' => self::CRUD],
        'estadisticas' => ['label' => 'Estadísticas del portal', 'group' => 'Configuración', 'description' => 'Visitantes, tiempo de visita, origen del tráfico, búsquedas y descargas del portal público. Solo lectura y sin datos personales.', 'actions' => [self::VIEW]],
        'actividad' => ['label' => 'Registro de actividad', 'group' => 'Configuración', 'description' => 'Historial de cambios e inicios de sesión.', 'actions' => [self::VIEW]],
        'redirecciones' => ['label' => 'Redirecciones', 'group' => 'Configuración', 'description' => 'Direcciones del portal anterior.', 'actions' => self::CRUD],
    ];

    /**
     * @return list<string>
     */
    public static function actionsFor(string $module): array
    {
        return self::MODULES[$module]['actions'] ?? [];
    }

    /**
     * @return array<string, string>
     */
    public static function actionOptions(string $module): array
    {
        return collect(self::actionsFor($module))
            ->mapWithKeys(fn (string $action): array => [$action => self::ACTION_LABELS[$action]])
            ->all();
    }

    /**
     * @return array<string, list<string>> Every action of every module.
     */
    public static function all(): array
    {
        return collect(self::MODULES)->map(fn (array $module): array => $module['actions'])->all();
    }

    /**
     * Keeps only known modules and actions, so stored roles can't hold invented permissions.
     * Any action implies "view" (the list is the way in), and "publish" implies "update"
     * because publishing is done from the edit form.
     *
     * @param  array<string, mixed>|null  $permissions
     * @return array<string, list<string>>
     */
    public static function sanitize(?array $permissions): array
    {
        $clean = [];

        foreach ($permissions ?? [] as $module => $actions) {
            if (! isset(self::MODULES[$module]) || ! is_array($actions) || array_intersect(self::actionsFor($module), $actions) === []) {
                continue;
            }

            $actions[] = self::VIEW;

            if (in_array(self::PUBLISH, $actions, true)) {
                $actions[] = self::UPDATE;
            }

            $clean[$module] = array_values(array_intersect(self::actionsFor($module), $actions));
        }

        return $clean;
    }

    /**
     * @param  array<string, list<string>>  $permissions
     */
    public static function summary(array $permissions): string
    {
        $modules = collect($permissions)->filter()->keys();

        return $modules->isEmpty()
            ? 'Sin permisos'
            : $modules->map(fn (string $module): string => self::MODULES[$module]['label'] ?? $module)->implode(', ');
    }
}
