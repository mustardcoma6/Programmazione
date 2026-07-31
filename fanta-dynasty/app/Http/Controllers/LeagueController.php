<?php

namespace App\Http\Controllers;

use App\Models\{League, LeagueParticipant, Roster, RealPlayer, User};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LeagueController extends Controller
{
    public function manageRosters()
{
    $user = auth()->user();
    $league = $user->leagues()->first();

    // Se non trova la lega, non bloccare tutto, ma vai avanti per vedere se la pagina carica
    if (!$league) {
        return Inertia::render('Admin/Rosters', [
            'league' => null,
            'teams' => [],
            'availablePlayers' => \App\Models\RealPlayer::limit(10)->get()
        ]);
    }

    // Carichiamo le squadre
    $teams = \App\Models\LeagueParticipant::where('league_id', $league->id)->with('user')->get();
    foreach ($teams as $team) {
        $team->players = \App\Models\Roster::where('league_id', $league->id)->where('user_id', $team->user_id)->with('player')->get();
    }

    $soldIds = \App\Models\Roster::where('league_id', $league->id)->pluck('real_player_id')->toArray();
    $availablePlayers = \App\Models\RealPlayer::whereNotIn('id', $soldIds)->orderBy('role', 'desc')->get();

    return Inertia::render('Admin/Rosters', [
        'league' => $league,
        'teams' => $teams,
        'availablePlayers' => $availablePlayers
    ]);
}

    // NUOVA FUNZIONE: MODIFICA CREDITI MANUALE
    public function updateCredits(Request $request)
    {
        $request->validate([
            'participant_id' => 'required|exists:league_participants,id',
            'new_credits' => 'required|integer|min:0'
        ]);

        $participant = LeagueParticipant::findOrFail($request->participant_id);
        
        // Sicurezza: solo l'admin della lega può farlo
        $league = League::find($participant->league_id);
        if (auth()->id() !== $league->admin_id) return back();

        $participant->update([
            'remaining_budget' => $request->new_credits
        ]);

        return back()->with('message', 'Budget aggiornato!');
    }

    public function assignPlayer(Request $request)
    {
        $request->validate(['player_id' => 'required', 'user_id' => 'required', 'price' => 'required|integer', 'years' => 'required|integer']);
        $league = auth()->user()->leagues()->first();
        Roster::create(['league_id' => $league->id, 'user_id' => $request->user_id, 'real_player_id' => $request->player_id, 'purchase_price' => $request->price, 'contract_years' => $request->years]);
        $p = LeagueParticipant::where('league_id', $league->id)->where('user_id', $request->user_id)->first();
        $p->decrement('remaining_budget', $request->price);
        return back();
    }

    public function removePlayer(Request $request)
    {
        $rosterItem = Roster::findOrFail($request->roster_id);
        $p = LeagueParticipant::where('league_id', $rosterItem->league_id)->where('user_id', $rosterItem->user_id)->first();
        $p->increment('remaining_budget', $rosterItem->purchase_price);
        $rosterItem->delete();
        return back();
    }

    public function societaIndex() { $user = auth()->user(); $league = $user->leagues()->first(); if (!$league) return redirect()->route('dashboard'); $participants = LeagueParticipant::where('league_id', $league->id)->with('user')->get(); foreach ($participants as $p) { $p->years_used = Roster::where('league_id', $league->id)->where('user_id', $p->user_id)->sum('contract_years'); } return Inertia::render('Societa/Index', ['league' => $league, 'participants' => $participants]); }
    public function create() { return Inertia::render('Leagues/Create'); }
    public function store(Request $request) { $request->validate(['name' => 'required', 'initial_budget' => 'required', 'team_name' => 'required']); $league = League::create(['name' => $request->name, 'invite_code' => strtoupper(Str::random(8)), 'admin_id' => auth()->id(), 'initial_budget' => $request->initial_budget]); LeagueParticipant::create(['league_id' => $league->id, 'user_id' => auth()->id(), 'team_name' => $request->team_name, 'remaining_budget' => $request->initial_budget, 'years_budget' => 40]); return redirect()->route('dashboard'); }
    public function join() { return Inertia::render('Leagues/Join'); }
    public function joinStore(Request $request) { $request->validate(['invite_code' => 'required', 'team_name' => 'required']); $league = League::where('invite_code', $request->invite_code)->first(); if (!$league) return back()->withErrors(['invite_code' => 'Codice errato']); LeagueParticipant::create(['league_id' => $league->id, 'user_id' => auth()->id(), 'team_name' => $request->team_name, 'remaining_budget' => $league->initial_budget, 'years_budget' => 40]); return redirect()->route('dashboard'); }
    public function toggleMarket(League $league) { if (auth()->id() !== $league->admin_id) return back(); $league->update(['is_market_open' => !$league->is_market_open]); return back(); }
    public function updateMarket(Request $request, League $league) { if (auth()->id() !== $league->admin_id) return back(); $league->update(['market_start_at' => $request->market_start_at, 'market_end_at' => $request->market_end_at]); $league->save(); return back(); }
}