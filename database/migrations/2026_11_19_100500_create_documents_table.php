<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('organizational_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->date('issued_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('external_url')->nullable();
            $table->unsignedInteger('downloads')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['document_type_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
