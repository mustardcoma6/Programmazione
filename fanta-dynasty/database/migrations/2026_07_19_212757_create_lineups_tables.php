<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Tabella principale Formazioni
        Schema::create('lineups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('matchday'); // Numero della giornata (es: 1, 2, 3...)
            $table->string('module')->default('4-4-2'); // Modulo scelto
            $table->timestamps();
        });

        // Dettaglio dei giocatori in formazione
        Schema::create('lineup_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lineup_id')->constrained()->onDelete('cascade');
            $table->foreignId('real_player_id')->constrained();
            $table->boolean('is_starter')->default(true); // Titolare o panchinaro?
            $table->integer('order')->default(0); // Ordine per la panchina
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('lineup_details');
        Schema::dropIfExists('lineups');
    }
};