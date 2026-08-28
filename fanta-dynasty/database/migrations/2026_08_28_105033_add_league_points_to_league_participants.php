<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('league_participants', function (Blueprint $table) {
            // Aggiungiamo i punti classifica (es: 3, 1, 0) dopo l'ID utente
            $table->integer('league_points')->default(0)->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('league_participants', function (Blueprint $table) {
            $table->dropColumn('league_points');
        });
    }
};