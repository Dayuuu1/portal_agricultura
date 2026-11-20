<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slides', function (Blueprint $table) {
            $table->string('eyebrow', 60)->nullable()->after('title');
            $table->string('background_image')->nullable()->after('image');
            $table->string('overlay', 10)->default('medium')->after('background_image');
            $table->timestamp('starts_at')->nullable()->after('sort_order');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('slides', function (Blueprint $table) {
            $table->dropColumn(['eyebrow', 'background_image', 'overlay', 'starts_at', 'ends_at']);
        });
    }
};
