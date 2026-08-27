<?php

public function up(): void
{
    Schema::table('league_participants', function (Blueprint $table) {
        $table->float('total_points')->default(0); // Punti totali (es: 720.5)
        $table->integer('games_played')->default(0); // Partite giocate (es: 10)
    });
}
