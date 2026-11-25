<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per page a visitor opened on the public site. No IP, cookie or account: "visitor" is a hash that
     * changes every day, so it only tells apart the visitors of a single day.
     */
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('visitor', 16);
            $table->string('session', 16);
            $table->boolean('is_entry')->default(false);
            $table->string('path');
            $table->string('title')->nullable();
            $table->string('section', 30);
            $table->string('source', 20)->nullable();
            $table->string('referrer_host', 120)->nullable();
            $table->string('utm_source', 60)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('device', 10);
            $table->string('browser', 20);
            $table->string('os', 20);
            $table->unsignedSmallInteger('duration')->nullable();
            $table->date('date');
            $table->unsignedTinyInteger('hour');
            $table->timestamp('created_at');

            $table->index('date');
            $table->index(['visitor', 'created_at']);
            $table->index('session');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
