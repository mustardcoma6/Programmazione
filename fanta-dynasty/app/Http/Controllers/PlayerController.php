<?php

namespace App\Http\Controllers;

use App\Models\RealPlayer;
use App\Models\Roster;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlayerController extends Controller
{
    // LISTA PUBBLICA SVINCOLATI
    public function index()
    {
        $league = auth()->user()->leagues()->first();
        if (!$league) return redirect()->route('dashboard');

        $soldIds = Roster::where('league_id', $league->id)->pluck('real_player_id')->toArray();
        $available = RealPlayer::whereNotIn('id', $soldIds)->orderBy('role', 'desc')->orderBy('name', 'asc')->get();

        return Inertia::render('Players/Index', ['players' => $available]);
    }

    // --- NUOVA GESTIONE ADMIN ---

    public function adminIndex()
    {
        // Mostra tutti i giocatori del database per permettere all'admin di gestirli
        $players = RealPlayer::orderBy('role', 'desc')->orderBy('name', 'asc')->get();
        return Inertia::render('Admin/Players', ['players' => $players]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|in:P,D,C,A',
            'real_team' => 'required|string|max:255',
        ]);

        RealPlayer::create([
            'name' => $request->name,
            'role' => $request->role,
            'real_team' => $request->real_team,
            'initial_value' => 1
        ]);

        return back()->with('message', 'Giocatore aggiunto al listone!');
    }

    public function destroy(RealPlayer $player)
    {
        // Controlliamo se è in una squadra (per evitare di rompere le rose)
        $isOwned = Roster::where('real_player_id', $player->id)->exists();
        
        if ($isOwned) {
            return back()->withErrors(['error' => 'Non puoi eliminare un giocatore che appartiene a una squadra! Svincolalo prima dalla gestione rose.']);
        }

        $player->delete();
        return back()->with('message', 'Giocatore rimosso dal listone.');
    }
}