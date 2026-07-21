<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Esegue la migrazione (Crea la tabella)
     */
    public function up(): void
    {
        Schema::create('rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('real_player_id')->constrained();
            $table->integer('purchase_price'); // Il prezzo pagato all'asta
            $table->timestamps();
        });
    }

    /**
     * Annulla la migrazione (Cancella la tabella)
     */
    public function down(): void
    {
        Schema::dropIfExists('rosters');
    }
};