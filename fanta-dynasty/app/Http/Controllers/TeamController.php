<?php

namespace App\Http\Controllers;

use App\Models\LeagueParticipant;
use App\Models\Roster;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $league = $user->leagues()->first();

        if (!$league) return redirect()->route('dashboard');

        // 1. Prendiamo tutti i partecipanti ordinati alfabeticamente per nome squadra
        $teams = LeagueParticipant::where('league_id', $league->id)
            ->with('user')
            ->orderBy('team_name', 'asc')
            ->get();

        // 2. Carichiamo i giocatori per ogni squadra con ordinamento ruoli specifico
        foreach ($teams as $team) {
            $team->players = Roster::where('league_id', $league->id)
                ->where('user_id', $team->user_id)
                ->join('real_players', 'rosters.real_player_id', '=', 'real_players.id')
                ->select('rosters.*', 'real_players.role', 'real_players.name', 'real_players.real_team')
                // Trucco SQL: Ordiniamo per ruolo P, D, C, A usando FIELD
                ->orderByRaw("FIELD(real_players.role, 'P', 'D', 'C', 'A')")
                ->orderBy('real_players.name', 'asc')
                ->get();
        }

        return Inertia::render('Teams/Index', [
            'teams' => $teams,
            'leagueName' => $league->name
        ]);
    }
}