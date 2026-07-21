<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aggiunge la colonna per lo stato del mercato.
     */
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            // Aggiungiamo l'interruttore del mercato
            $table->boolean('is_market_open')->default(false);
        });
    }

    /**
     * Torna indietro (Rimuove la colonna).
     */
    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn('is_market_open');
        });
    }
};