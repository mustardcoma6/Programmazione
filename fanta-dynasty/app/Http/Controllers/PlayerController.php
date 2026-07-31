<?php

namespace App\Http\Controllers;

use App\Models\RealPlayer;
use App\Models\Roster;
use App\Models\Auction;
use App\Models\LineupDetail;
use App\Models\Autobid;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlayerController extends Controller
{
    // ... [le altre funzioni index, adminIndex e store rimangono uguali] ...

    public function destroy(RealPlayer $player)
    {
        // 1. Controllo di sicurezza: se il giocatore è in una squadra, blocca tutto
        $isOwned = Roster::where('real_player_id', $player->id)->exists();
        if ($isOwned) {
            return back()->withErrors(['error' => 'Impossibile eliminare: il giocatore appartiene alla rosa di una squadra!']);
        }

        // 2. PULIZIA DEI COLLEGAMENTI (Per evitare QueryException)
        // Eliminiamo eventuali rilanci automatici legati alle aste di questo giocatore
        $auctionIds = Auction::where('real_player_id', $player->id)->pluck('id');
        Autobid::whereIn('auction_id', $auctionIds)->delete();

        // Eliminiamo la storia delle aste di questo giocatore
        Auction::where('real_player_id', $player->id)->delete();

        // Eliminiamo il giocatore dalle formazioni schierate (passate o future)
        LineupDetail::where('real_player_id', $player->id)->delete();

        // 3. Ora che il tavolo è pulito, cancelliamo il giocatore
        $player->delete();

        return back()->with('message', 'Giocatore rimosso definitivamente dal sistema.');
    }
    
    // Incollo qui le altre funzioni per comodità del tuo copia-incolla integrale
    public function index() { $league = auth()->user()->leagues()->first(); if (!$league) return redirect()->route('dashboard'); $soldIds = Roster::where('league_id', $league->id)->pluck('real_player_id')->toArray(); $available = RealPlayer::whereNotIn('id', $soldIds)->orderBy('role', 'desc')->orderBy('name', 'asc')->get(); return Inertia::render('Players/Index', ['players' => $available]); }
    public function adminIndex() { $players = RealPlayer::orderBy('role', 'desc')->orderBy('name', 'asc')->get(); return Inertia::render('Admin/Players', ['players' => $players]); }
    public function store(Request $request) { $request->validate(['name' => 'required|string|max:255', 'role' => 'required|in:P,D,C,A', 'real_team' => 'required|string|max:255']); RealPlayer::create(['name' => $request->name, 'role' => $request->role, 'real_team' => $request->real_team, 'initial_value' => 1]); return back(); }
}