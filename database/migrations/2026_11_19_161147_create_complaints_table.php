<?php

use App\Models\Role;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Libro de Reclamaciones (DS N° 007-2020-PCM): claims registered from the portal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();

            $table->string('full_name');
            $table->string('document_type', 20);
            $table->string('document_number', 20);
            $table->string('address');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->boolean('is_minor')->default(false);
            $table->string('guardian_name')->nullable();
            $table->string('guardian_document_number', 20)->nullable();

            $table->date('incident_date');
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organizational_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 30);
            $table->text('description');
            $table->text('request')->nullable();
            $table->json('attachments')->nullable();
            $table->boolean('notify_by_email')->default(false);

            $table->string('status', 20)->default('registrado')->index();
            $table->date('due_on');
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Setting::query()->firstOrCreate(['key' => 'complaints_email'], [
            'label' => 'Correo que recibe el Libro de Reclamaciones',
            'value' => 'info@dramdd.gob.pe',
            'group' => 'servicios',
            'type' => 'email',
            'sort_order' => 16,
        ]);

        Setting::query()->where('key', 'libro_reclamaciones_url')
            ->update(['label' => 'Libro de reclamaciones en GOB.PE (opcional: enlace a la plataforma digital del Estado)']);

        // The citizen-service role created on 2026-11-19 is the natural owner of the new module.
        Role::query()->where('name', 'Atención al ciudadano')->get()->each(function (Role $role): void {
            $role->update(['permissions' => [...($role->permissions ?? []), 'reclamaciones' => ['view', 'update']]]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
        Setting::query()->where('key', 'complaints_email')->delete();
    }
};
