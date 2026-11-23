<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Until the Mesa de Partes queries RENIEC/SUNAT, anyone could open an account with someone else's document.
 * The staff records here when, and who, checked that the document belongs to the person behind the account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->timestamp('identity_verified_at')->nullable()->after('email_verified_at');
            $table->foreignId('identity_verified_by')->nullable()->after('identity_verified_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('identity_verified_by');
            $table->dropColumn('identity_verified_at');
        });
    }
};
