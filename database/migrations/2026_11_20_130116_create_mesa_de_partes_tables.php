<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mesa de Partes Virtual (Ley N° 31170; TUO de la Ley N° 27444, D.S. N° 004-2019-JUS).
 *
 * Citizens hold their own accounts (separate from the panel users), file documents, receive observations and
 * answers in an inbox and follow each file's route. The service starts disabled: it is switched on from the
 * panel once the entity approves it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizens', function (Blueprint $table) {
            $table->id();
            $table->string('person_type', 20)->default('natural');
            $table->string('document_type', 20);
            $table->string('document_number', 20);
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('business_name', 200)->nullable();
            $table->string('representative_name', 150)->nullable();
            $table->string('representative_document_number', 20)->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone', 20);
            $table->string('department', 60);
            $table->string('province', 80);
            $table->string('district', 80);
            $table->string('address');
            $table->string('password');
            $table->timestamp('privacy_accepted_at')->nullable();
            $table->timestamp('truthfulness_declared_at')->nullable();
            $table->timestamp('e_notifications_authorized_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['document_type', 'document_number']);
        });

        Schema::create('citizen_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('procedure_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->unsignedSmallInteger('response_days')->nullable();
            $table->decimal('fee', 10, 2)->nullable();
            $table->boolean('requires_payment_proof')->default(false);
            $table->foreignId('default_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('citizen_id')->constrained()->restrictOnDelete();
            $table->foreignId('procedure_type_id')->constrained()->restrictOnDelete();
            $table->string('subject');
            $table->text('description');
            $table->string('related_code', 60)->nullable();
            $table->foreignId('target_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->foreignId('current_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->string('status', 30)->default('recibido')->index();
            $table->timestamp('submitted_at');
            $table->date('filed_on');
            $table->date('due_on')->nullable();
            $table->text('observation')->nullable();
            $table->timestamp('observed_at')->nullable();
            $table->date('cure_deadline')->nullable();
            $table->timestamp('cured_at')->nullable();
            $table->text('response')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('folios')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('submission_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('pages')->default(1);
            $table->char('sha256', 64);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('submission_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->string('action', 30);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('citizen_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citizen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->timestamp('deposited_at');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('non_working_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('description');
            $table->timestamps();
        });

        foreach ($this->settings() as $order => [$key, $label, $type, $value]) {
            Setting::query()->firstOrCreate(['key' => $key], [
                'label' => $label, 'value' => $value, 'group' => 'mesa_partes', 'type' => $type, 'sort_order' => 100 + $order,
            ]);
        }

        // Legal categories every entity must receive; each one can be edited or deactivated in the panel.
        $now = now();
        DB::table('procedure_types')->insertOrIgnore([
            ['name' => 'Solicitud o escrito', 'slug' => 'solicitud-o-escrito', 'sort_order' => 1, 'response_days' => 30, 'created_at' => $now, 'updated_at' => $now,
                'description' => 'Cualquier pedido, comunicación o documento dirigido a la entidad que no tenga un trámite propio en el TUPA.',
                'requirements' => "Escrito dirigido a la entidad, con su nombre, documento de identidad, domicilio, lo que solicita y sus fundamentos, firmado.\nAnexos que sustenten su pedido, si los tuviera."],
            ['name' => 'Acceso a la información pública', 'slug' => 'acceso-a-la-informacion-publica', 'sort_order' => 2, 'response_days' => 10, 'created_at' => $now, 'updated_at' => $now,
                'description' => 'Pedido de información que posee la entidad (TUO de la Ley N° 27806, Ley de Transparencia y Acceso a la Información Pública).',
                'requirements' => "Indique con claridad la información que solicita y la forma en que desea recibirla (correo electrónico o copia).\nSi la entrega tiene costo de reproducción, se le informará antes de entregarla."],
            ['name' => 'Documento para un expediente en trámite', 'slug' => 'documento-para-expediente-en-tramite', 'sort_order' => 3, 'response_days' => 0, 'created_at' => $now, 'updated_at' => $now,
                'description' => 'Escrito o anexo que complementa un trámite ya iniciado. Indique el número de expediente.',
                'requirements' => 'Número del expediente o trámite al que corresponde el documento.'],
        ]);
    }

    public function down(): void
    {
        Setting::query()->where('group', 'mesa_partes')->delete();

        Schema::dropIfExists('non_working_days');
        Schema::dropIfExists('citizen_notifications');
        Schema::dropIfExists('submission_movements');
        Schema::dropIfExists('submission_files');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('procedure_types');
        Schema::dropIfExists('citizen_password_reset_tokens');
        Schema::dropIfExists('citizens');
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: string|null}>
     */
    private function settings(): array
    {
        return [
            ['mpv_enabled', 'Recepción habilitada', 'boolean', '0'],
            ['mpv_opening_time', 'Hora de inicio de la atención', 'time', '08:00'],
            ['mpv_closing_time', 'Hora de cierre para el cómputo de plazos', 'time', '16:00'],
            ['mpv_cure_days', 'Plazo para subsanar observaciones (días hábiles)', 'number', '2'],
            ['mpv_response_days', 'Plazo de atención por defecto (días hábiles)', 'number', '30'],
            ['mpv_max_file_mb', 'Tamaño máximo por archivo (MB)', 'number', '10'],
            ['mpv_max_total_mb', 'Tamaño máximo por trámite (MB)', 'number', '30'],
            ['mpv_max_attachments', 'Cantidad máxima de anexos', 'number', '10'],
            ['mpv_allow_legal_entities', 'Permitir cuentas de personas jurídicas', 'boolean', '1'],
            ['mpv_public_lookup', 'Consulta de expedientes sin cuenta', 'boolean', '1'],
            ['mpv_staff_email', 'Correo que recibe los avisos de nuevos trámites', 'email', null],
            ['mpv_code_prefix', 'Prefijo del número de expediente', 'text', 'MPV'],
            ['mpv_approval', 'Norma que aprueba la mesa de partes virtual', 'text', null],
            ['mpv_instructions', 'Indicaciones adicionales para la ciudadanía', 'textarea', null],
            ['mpv_legal_basis', 'Base legal', 'textarea', implode("\n", [
                'Ley N° 31170, Ley que dispone la implementación de mesas de partes digitales y notificaciones electrónicas.',
                'Texto Único Ordenado de la Ley N° 27444, Ley del Procedimiento Administrativo General (D.S. N° 004-2019-JUS).',
                'Decreto Legislativo N° 1412, Ley de Gobierno Digital, y su Reglamento (D.S. N° 029-2021-PCM).',
                'Ley N° 29733, Ley de Protección de Datos Personales, y su Reglamento (D.S. N° 016-2024-JUS).',
            ])],
        ];
    }
};
