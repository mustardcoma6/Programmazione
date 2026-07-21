<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabella delle Leghe
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('invite_code')->unique();
            $table->foreignId('admin_id')->constrained('users');
            $table->integer('initial_budget')->default(500);
            $table->timestamps();
        });

        // Tabella del Listone Calciatori
        Schema::create('real_players', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('role', ['P', 'D', 'C', 'A']);
            $table->string('real_team');
            $table->integer('initial_value')->default(1);
            $table->timestamps();
        });

        // Tabella Partecipanti (Squadre degli utenti)
        Schema::create('league_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('team_name');
            $table->integer('remaining_budget');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_participants');
        Schema::dropIfExists('real_players');
        Schema::dropIfExists('leagues');
    }
};