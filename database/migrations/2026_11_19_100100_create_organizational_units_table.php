<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizational_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('acronym')->nullable();
            $table->string('type');
            $table->text('description')->nullable();
            $table->longText('functions')->nullable();
            $table->string('head_name')->nullable();
            $table->string('head_title')->nullable();
            $table->string('head_photo')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizational_units');
    }
};
