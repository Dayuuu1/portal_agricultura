<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedBigInteger('wp_id')->nullable()->unique();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedBigInteger('wp_id')->nullable()->unique();
            $table->string('file_hash', 32)->nullable()->index();
        });

        Schema::table('job_postings', function (Blueprint $table) {
            $table->string('import_key')->nullable()->unique();
        });

        Schema::table('job_posting_files', function (Blueprint $table) {
            $table->unsignedBigInteger('wp_id')->nullable()->unique();
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('old_path')->unique();
            $table->string('new_path');
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');

        Schema::table('posts', function (Blueprint $table) {
            $table->dropUnique(['wp_id']);
            $table->dropColumn('wp_id');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropUnique(['wp_id']);
            $table->dropIndex(['file_hash']);
            $table->dropColumn(['wp_id', 'file_hash']);
        });

        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropUnique(['import_key']);
            $table->dropColumn('import_key');
        });

        Schema::table('job_posting_files', function (Blueprint $table) {
            $table->dropUnique(['wp_id']);
            $table->dropColumn('wp_id');
        });
    }
};
