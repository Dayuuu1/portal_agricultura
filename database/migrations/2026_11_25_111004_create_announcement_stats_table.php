<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per announcement and day: counts only, nothing about who the visitor is.
     */
    public function up(): void
    {
        Schema::create('announcement_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('dismissals')->default(0);

            $table->unique(['announcement_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_stats');
    }
};
