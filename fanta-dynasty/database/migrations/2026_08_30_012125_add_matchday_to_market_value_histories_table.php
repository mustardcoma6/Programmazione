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
    Schema::table('market_value_histories', function (Blueprint $table) {
        // Aggiungiamo la colonna matchday per sapere a quale giornata si riferisce il valore
        $table->integer('matchday')->default(1)->after('league_id');
    });
}