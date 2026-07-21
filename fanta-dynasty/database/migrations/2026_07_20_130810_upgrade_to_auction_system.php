<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dateTime('market_start_at')->nullable();
            $table->dateTime('market_end_at')->nullable();
        });

        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->onDelete('cascade');
            $table->foreignId('real_player_id')->constrained();
            $table->foreignId('user_id')->constrained(); // L'attuale miglior offerente
            $table->integer('current_bid');
            $table->dateTime('expires_at'); // Quando scade (90 min dopo l'offerta)
            $table->boolean('is_finished')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('auctions');
    }
};