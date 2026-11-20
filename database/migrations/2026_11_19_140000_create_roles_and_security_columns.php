<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Former fixed areas and the modules each one covered.
     */
    private const AREA_MODULES = [
        'contenido' => ['Contenido', ['noticias', 'categorias', 'paginas', 'banners']],
        'documentos' => ['Documentos', ['documentos', 'tipos_documento']],
        'convocatorias' => ['Convocatorias', ['convocatorias']],
        'institucional' => ['Institucional', ['unidades', 'agencias', 'enlaces']],
        'atencion' => ['Atención al ciudadano', ['mensajes']],
    ];

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->json('permissions')->nullable();
            $table->json('document_type_ids')->nullable();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();
        });

        $this->convertAreasToRoles();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('permissions')->nullable();
            $table->dropColumn(['app_authentication_secret', 'app_authentication_recovery_codes']);
        });

        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }

    /**
     * Users that had areas keep the same access through equivalent roles.
     */
    private function convertAreasToRoles(): void
    {
        $roleIds = [];

        foreach (DB::table('users')->whereNotNull('permissions')->get(['id', 'permissions']) as $user) {
            foreach (json_decode((string) $user->permissions, true) ?: [] as $area) {
                if (! isset(self::AREA_MODULES[$area])) {
                    continue;
                }

                [$name, $modules] = self::AREA_MODULES[$area];

                $roleIds[$area] ??= DB::table('roles')->insertGetId([
                    'name' => $name,
                    'description' => 'Rol creado a partir del área «'.$name.'» del sistema anterior.',
                    'permissions' => json_encode(array_fill_keys($modules, ['view', 'create', 'update', 'delete', 'publish'])),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('role_user')->insertOrIgnore(['role_id' => $roleIds[$area], 'user_id' => $user->id]);
            }
        }
    }
};
