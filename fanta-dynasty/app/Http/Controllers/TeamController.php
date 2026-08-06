<?php

namespace App\Http\Controllers;

use App\Models\{LeagueParticipant, Roster, PrimaveraRoster};
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $league = $user->leagues()->first();
        if (!$league) return redirect()->route('dashboard');

        $teams = LeagueParticipant::where('league_id', $league->id)->with('user')->orderBy('team_name', 'asc')->get();

        foreach ($teams as $team) {
            // Prima Squadra
            $pros = Roster::where('league_id', $league->id)->where('user_id', $team->user_id)->with('player')->get()
                ->map(fn($p) => { $p->is_primavera = false; return $p; });
            
            // Primavera
            $juniors = PrimaveraRoster::where('league_id', $league->id)->where('user_id', $team->user_id)->with('player')->get()
                ->map(fn($p) => { $p->is_primavera = true; return $p; });

            // Unione e Ordinamento Ruolo (P,D,C,A)
            $team->players = $pros->concat($juniors)->sortBy([
                fn ($a, $b) => array_search($a->player->role, ['P', 'D', 'C', 'A']) <=> array_search($b->player->role, ['P', 'D', 'C', 'A']),
                ['player.name', 'asc']
            ])->values()->all();
        }

        return Inertia::render('Teams/Index', ['teams' => $teams, 'leagueName' => $league->name]);
    }
}