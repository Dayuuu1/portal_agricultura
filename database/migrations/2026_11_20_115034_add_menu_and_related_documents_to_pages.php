<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pages can now be listed in the «Institucional» menu and point to documents of the repository
 * (for example, the approved PDF of the code of ethics). Only columns and a pivot table are added; the
 * code of ethics is flagged for the menu because that is where it already appeared.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('show_in_menu')->default(false)->after('is_published');
        });

        Schema::create('document_page', function (Blueprint $table) {
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['page_id', 'document_id']);
        });

        DB::table('pages')->where('slug', 'codigo-de-etica')->update(['show_in_menu' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('document_page');

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('show_in_menu');
        });
    }
};
