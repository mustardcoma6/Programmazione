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
            // Aggiungiamo i punti (float permette i decimali tipo 72.5)
            // e le partite giocate. Li mettiamo dopo il campo user_id per ordine.
            $table->float('total_points')->default(0)->after('user_id');
            $table->integer('games_played')->default(0)->after('total_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('league_participants', function (Blueprint $table) {
            $table->dropColumn(['total_points', 'games_played']);
        });
    }
};