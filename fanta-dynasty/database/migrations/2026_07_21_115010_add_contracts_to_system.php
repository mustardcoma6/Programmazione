<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Aggiungiamo il budget anni alla squadra
        Schema::table('league_participants', function (Blueprint $table) {
            $table->integer('years_budget')->default(40); // Esempio: 40 anni totali
        });

        // Aggiungiamo gli anni di contratto al calciatore acquistato
        Schema::table('rosters', function (Blueprint $table) {
            $table->integer('contract_years')->default(1); // Minimo 1 anno
        });
    }

    public function down(): void {
        Schema::table('league_participants', function (Blueprint $table) { $table->dropColumn('years_budget'); });
        Schema::table('rosters', function (Blueprint $table) { $table->dropColumn('contract_years'); });
    }
};