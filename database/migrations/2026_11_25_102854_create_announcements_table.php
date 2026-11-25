<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20)->default('notice');
            $table->string('title', 150);
            $table->text('body')->nullable();
            $table->string('image')->nullable();
            $table->string('layout', 10)->default('card');
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->string('button_text', 40)->nullable();
            $table->string('button_url')->nullable();
            $table->string('placement', 12)->default('home');
            $table->string('frequency', 10)->default('once');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
