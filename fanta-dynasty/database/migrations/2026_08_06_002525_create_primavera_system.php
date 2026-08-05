<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Aggiungiamo il budget specifico per la primavera alla squadra
        Schema::table('league_participants', function (Blueprint $table) {
            $table->integer('remaining_primavera_budget')->default(0);
        });

        // Tabella per i calciatori assegnati alla Primavera
        Schema::create('primavera_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('real_player_id')->constrained();
            $table->integer('purchase_price');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('primavera_rosters');
        Schema::table('league_participants', function (Blueprint $table) {
            $table->dropColumn('remaining_primavera_budget');
        });
    }
};