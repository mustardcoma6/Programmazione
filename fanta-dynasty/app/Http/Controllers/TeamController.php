<?php

namespace App\Http\Controllers;

use App\Models\{LeagueParticipant, Roster, PrimaveraRoster, League};
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
            // Prima Squadra - Corretto con sintassi function standard
            $pros = Roster::where('league_id', $league->id)->where('user_id', $team->user_id)->with('player')->get()
                ->map(function($p) { 
                    $p->is_primavera = false; 
                    return $p; 
                });
            
            // Primavera - Corretto con sintassi function standard
            $juniors = PrimaveraRoster::where('league_id', $league->id)->where('user_id', $team->user_id)->with('player')->get()
                ->map(function($p) { 
                    $p->is_primavera = true; 
                    return $p; 
                });

            // Unione e Ordinamento Ruolo (P,D,C,A)
            $team->players = $pros->concat($juniors)->sortBy([
                function ($a, $b) {
                    $order = ['P' => 1, 'D' => 2, 'C' => 3, 'A' => 4];
                    return $order[$a->player->role] <=> $order[$b->player->role];
                },
                ['player.name', 'asc']
            ])->values()->all();
        }

        return Inertia::render('Teams/Index', ['teams' => $teams, 'leagueName' => $league->name]);
    }
}