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

        // 1. Prendiamo tutti i partecipanti della lega
        $teams = LeagueParticipant::where('league_id', $league->id)->get();

        // 2. Per ogni squadra, carichiamo i calciatori comprati IN QUESTA LEGA
        foreach ($teams as $team) {
            $team->roster = Roster::where('league_id', $league->id)
                ->where('user_id', $team->user_id)
                ->with('player')
                ->get();
        }

        return Inertia::render('Teams/Index', [
            'teams' => $teams,
            'leagueName' => $league->name
        ]);
    }
}