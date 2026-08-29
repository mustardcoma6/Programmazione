<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('market_value_histories', function (Blueprint $table) {
            // Aggiungiamo la colonna matchday per il grafico
            $table->integer('matchday')->default(1)->after('league_id');
        });
    }

    public function down(): void
    {
        Schema::table('market_value_histories', function (Blueprint $table) {
            $table->dropColumn('matchday');
        });
    }
};