<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What visitors did inside a page: searches (with the number of results), document downloads and links to
     * other sites.
     */
    public function up(): void
    {
        Schema::create('site_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_view_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 15);
            $table->string('label', 150);
            $table->string('context', 30)->nullable();
            $table->unsignedInteger('value')->nullable();
            $table->date('date');
            $table->timestamp('created_at');

            $table->index(['type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_events');
    }
};
