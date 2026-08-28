<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

public function up(): void
{
    Schema::table('league_participants', function (Blueprint $table) {
        // Aggiungiamo i punti classifica (es: 3, 6, 9...)
        $table->integer('league_points')->default(0)->after('user_id');
    });
}

public function down(): void
{
    Schema::table('league_participants', function (Blueprint $table) {
        $table->dropColumn('league_points');
    });
}