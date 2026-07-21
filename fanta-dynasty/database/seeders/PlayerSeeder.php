<?php

namespace Database\Seeders;

use App\Models\RealPlayer;
use Illuminate\Database\Seeder;

class PlayerSeeder extends Seeder
{
    public function run(): void
    {
        $players = [
            // PORTIERI (P)
            ['name' => 'Yann Sommer', 'role' => 'P', 'real_team' => 'Inter', 'initial_value' => 18],
            ['name' => 'Michele Di Gregorio', 'role' => 'P', 'real_team' => 'Juventus', 'initial_value' => 15],
            ['name' => 'Ivan Provedel', 'role' => 'P', 'real_team' => 'Lazio', 'initial_value' => 14],
            ['name' => 'Alex Meret', 'role' => 'P', 'real_team' => 'Napoli', 'initial_value' => 13],

            // DIFENSORI (D)
            ['name' => 'Federico Dimarco', 'role' => 'D', 'real_team' => 'Inter', 'initial_value' => 22],
            ['name' => 'Gleison Bremer', 'role' => 'D', 'real_team' => 'Juventus', 'initial_value' => 18],
            ['name' => 'Alessandro Bastoni', 'role' => 'D', 'real_team' => 'Inter', 'initial_value' => 16],
            ['name' => 'Theo Hernandez', 'role' => 'D', 'real_team' => 'Milan', 'initial_value' => 20],
            ['name' => 'Gianluca Mancini', 'role' => 'D', 'real_team' => 'Roma', 'initial_value' => 13],
            ['name' => 'Denzel Dumfries', 'role' => 'D', 'real_team' => 'Inter', 'initial_value' => 14],
            ['name' => 'Giovanni Di Lorenzo', 'role' => 'D', 'real_team' => 'Napoli', 'initial_value' => 15],
            ['name' => 'Matteo Ruggeri', 'role' => 'D', 'real_team' => 'Atalanta', 'initial_value' => 10],
            ['name' => 'Stefan Posch', 'role' => 'D', 'real_team' => 'Bologna', 'initial_value' => 11],

            // CENTROCAMPISTI (C)
            ['name' => 'Hakan Calhanoglu', 'role' => 'C', 'real_team' => 'Inter', 'initial_value' => 25],
            ['name' => 'Teun Koopmeiners', 'role' => 'C', 'real_team' => 'Juventus', 'initial_value' => 24],
            ['name' => 'Christian Pulisic', 'role' => 'C', 'real_team' => 'Milan', 'initial_value' => 23],
            ['name' => 'Nicolo Barella', 'role' => 'C', 'real_team' => 'Inter', 'initial_value' => 20],
            ['name' => 'Lorenzo Pellegrini', 'role' => 'C', 'real_team' => 'Roma', 'initial_value' => 18],
            ['name' => 'Ederson', 'role' => 'C', 'real_team' => 'Atalanta', 'initial_value' => 16],
            ['name' => 'Henrikh Mkhitaryan', 'role' => 'C', 'real_team' => 'Inter', 'initial_value' => 17],
            ['name' => 'Matteo Politano', 'role' => 'C', 'real_team' => 'Napoli', 'initial_value' => 19],
            ['name' => 'Lewis Ferguson', 'role' => 'C', 'real_team' => 'Bologna', 'initial_value' => 18],
            ['name' => 'Mario Pasalic', 'role' => 'C', 'real_team' => 'Atalanta', 'initial_value' => 15],

            // ATTACCANTI (A)
            ['name' => 'Lautaro Martinez', 'role' => 'A', 'real_team' => 'Inter', 'initial_value' => 40],
            ['name' => 'Dusan Vlahovic', 'role' => 'A', 'real_team' => 'Juventus', 'initial_value' => 35],
            ['name' => 'Victor Osimhen', 'role' => 'A', 'real_team' => 'Napoli', 'initial_value' => 38],
            ['name' => 'Rafael Leao', 'role' => 'A', 'real_team' => 'Milan', 'initial_value' => 30],
            ['name' => 'Paulo Dybala', 'role' => 'A', 'real_team' => 'Roma', 'initial_value' => 32],
            ['name' => 'Marcus Thuram', 'role' => 'A', 'real_team' => 'Inter', 'initial_value' => 28],
            ['name' => 'Duvan Zapata', 'role' => 'A', 'real_team' => 'Torino', 'initial_value' => 25],
            ['name' => 'Mateegui Retegui', 'role' => 'A', 'real_team' => 'Atalanta', 'initial_value' => 24],
            ['name' => 'Gianluca Scamacca', 'role' => 'A', 'real_team' => 'Atalanta', 'initial_value' => 26],
            ['name' => 'Albert Gudmundsson', 'role' => 'A', 'real_team' => 'Fiorentina', 'initial_value' => 22],
            ['name' => 'Taty Castellanos', 'role' => 'A', 'real_team' => 'Lazio', 'initial_value' => 20],
        ];

        foreach ($players as $player) {
            // Usiamo updateOrCreate per evitare di duplicare quelli che avevamo già inserito ieri
            RealPlayer::updateOrCreate(['name' => $player['name']], $player);
        }
    }
}