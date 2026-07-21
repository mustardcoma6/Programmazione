<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Esegue la migrazione.
     */
    public function up(): void
    {
        Schema::table('rosters', function (Blueprint $table) {
            // Aggiungiamo la colonna per la clausola
            $table->integer('release_clause')->default(0);
        });
    }

    /**
     * Annulla la migrazione.
     */
    public function down(): void
    {
        Schema::table('rosters', function (Blueprint $table) {
            $table->dropColumn('release_clause');
        });
    }
};