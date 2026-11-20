<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedSmallInteger('period_year')->nullable()->after('issued_at');
            $table->index(['document_type_id', 'period_year']);
        });

        Schema::table('document_types', function (Blueprint $table) {
            $table->boolean('group_by_year')->default(false)->after('section');
        });

        DB::table('documents')->orderBy('id')->chunkById(500, function ($documents): void {
            foreach ($documents as $document) {
                DB::table('documents')->where('id', $document->id)->update([
                    'period_year' => $this->yearFromTitle((string) $document->title)
                        ?? ($document->issued_at ? (int) substr((string) $document->issued_at, 0, 4) : null),
                ]);
            }
        });

        DB::table('document_types')
            ->where('section', 'informacion-agraria')
            ->orWhereIn('name', ['Resoluciones Directorales', 'Cuadro Multianual de Necesidades', 'Planeamiento y Presupuesto'])
            ->update(['group_by_year' => true]);
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['document_type_id', 'period_year']);
            $table->dropColumn('period_year');
        });

        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('group_by_year');
        });
    }

    private function yearFromTitle(string $title): ?int
    {
        preg_match_all('/(?<!\d)(19[89]\d|20\d{2})(?!\d)/', $title, $matches);

        foreach ($matches[1] as $year) {
            if ((int) $year <= (int) date('Y') + 1) {
                return (int) $year;
            }
        }

        return null;
    }
};
