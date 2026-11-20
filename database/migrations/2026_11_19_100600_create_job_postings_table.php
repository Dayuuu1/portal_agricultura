<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizational_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('modality')->default('CAS');
            $table->unsignedSmallInteger('positions_count')->default(1);
            $table->longText('description')->nullable();
            $table->string('status')->default('en-curso');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
