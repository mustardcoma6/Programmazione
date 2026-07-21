<?php

namespace App\Http\Controllers;

use App\Models\RealPlayer;
use App\Models\Roster;
use Inertia\Inertia;

class PlayerController extends Controller
{
    public function index()
    {
        $league = auth()->user()->leagues()->first();
        if (!$league) return redirect()->route('dashboard');

        // Prendiamo gli ID di chi ha già una squadra in questa lega
        $soldIds = Roster::where('league_id', $league->id)->pluck('real_player_id')->toArray();

        // Mostriamo solo chi NON ha squadra
        $available = RealPlayer::whereNotIn('id', $soldIds)
            ->orderBy('role', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        return Inertia::render('Players/Index', [
            'players' => $available
        ]);
    }
}