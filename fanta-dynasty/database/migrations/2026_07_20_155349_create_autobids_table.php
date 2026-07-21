<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('autobids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('max_bid'); // Il tetto massimo dell'utente
            $table->timestamps();
            $table->unique(['auction_id', 'user_id']); // Un solo autobid per utente su quel giocatore
        });
    }
    public function down(): void { Schema::dropIfExists('autobids'); }
};