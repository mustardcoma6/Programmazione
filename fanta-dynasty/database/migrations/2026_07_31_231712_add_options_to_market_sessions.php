<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('market_sessions', function (Blueprint $table) {
            // Durata in minuti per questa sessione (default 90)
            $table->integer('auction_duration')->default(90);
            // Ruoli permessi salvati come testo (es: "P,D,C")
            $table->string('allowed_roles')->default('P,D,C,A');
        });
    }
    public function down(): void {
        Schema::table('market_sessions', function (Blueprint $table) {
            $table->dropColumn(['auction_duration', 'allowed_roles']);
        });
    }
};