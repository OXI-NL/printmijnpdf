<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Gearchiveerd: de order blijft bewaard (fiscale bewaarplicht), de PDF niet (privacyverklaring)
            $table->timestamp('archived_at')->nullable()->after('shipped_at');
            $table->index('archived_at');

            // Na opruimen of archiveren is er geen PDF meer; pdfs:cleanup zette dit al op null
            $table->string('pdf_stored_name')->nullable()->change();
            $table->string('pdf_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
