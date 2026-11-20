<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->boolean('show_in_carousel')->default(false)->after('group');
        });

        // The home page used to list the "Enlaces de interés" group: keep those in the new carousel.
        DB::table('links')->where('group', 'enlaces-interes')->update(['show_in_carousel' => true]);
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn('show_in_carousel');
        });
    }
};
