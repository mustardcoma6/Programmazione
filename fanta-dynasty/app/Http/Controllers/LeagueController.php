<?php

namespace App\Http\Controllers;

use App\Models\{League, LeagueParticipant, Roster, RealPlayer, User, Auction, Lineup, LineupDetail, Autobid};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class LeagueController extends Controller
{
    // MOSTRA ANAGRAFE SOCIETARIA
    public function societaIndex()
    {
        $user = auth()->user();
        $league = $user->leagues()->first();
        if (!$league) return redirect()->route('dashboard');

        $participants = LeagueParticipant::where('league_id', $league->id)->with('user')->get();

        foreach ($participants as $p) {
            $p->years_used = Roster::where('league_id', $league->id)->where('user_id', $p->user_id)->sum('contract_years');
        }

        return Inertia::render('Societa/Index', ['league' => $league, 'participants' => $participants]);
    }

    public function kickParticipant(LeagueParticipant $participant)
    {
        $user = auth()->user();
        $league = League::find($participant->league_id);
        if ($league->admin_id !== $user->id) return back();
        if ($participant->user_id === $user->id) return back();

        DB::transaction(function () use ($participant, $league) {
            Roster::where('league_id', $league->id)->where('user_id', $participant->user_id)->delete();
            $lineups = Lineup::where('league_id', $league->id)->where('user_id', $participant->user_id)->pluck('id');
            LineupDetail::whereIn('lineup_id', $lineups)->delete();
            Lineup::whereIn('id', $lineups)->delete();
            Auction::where('league_id', $league->id)->where('user_id', $participant->user_id)->delete();
            Autobid::where('user_id', $participant->user_id)->delete();
            $participant->delete();
        });
        return back();
    }

    // [Mantieni tutte le altre funzioni manageRosters, manageCredits, etc. che abbiamo scritto prima...]
    public function manageRosters() { $user = auth()->user(); $league = League::where('admin_id', $user->id)->first(); if (!$league) return redirect()->route('dashboard'); $teams = LeagueParticipant::where('league_id', $league->id)->with('user')->get(); foreach ($teams as $team) { $team->players = Roster::where('league_id', $league->id)->where('user_id', $team->user_id)->with('player')->get(); } $soldIds = Roster::where('league_id', $league->id)->pluck('real_player_id')->toArray(); $availablePlayers = RealPlayer::whereNotIn('id', $soldIds)->orderBy('role', 'desc')->limit(100)->get(); return Inertia::render('Admin/Rosters', ['league' => $league, 'teams' => $teams, 'availablePlayers' => $availablePlayers]); }
    public function manageCredits() { $user = auth()->user(); $league = League::where('admin_id', $user->id)->first(); if (!$league) return redirect()->route('dashboard'); $teams = LeagueParticipant::where('league_id', $league->id)->with('user')->get(); return Inertia::render('Admin/Credits', ['league' => $league, 'teams' => $teams]); }
    public function updateResources(Request $request) { $p = LeagueParticipant::findOrFail($request->participant_id); $p->update(['remaining_budget' => $request->new_credits, 'years_budget' => $request->new_years]); return back(); }
    public function assignManualPlayer(Request $request) { $league = auth()->user()->leagues()->first(); $newPlayer = RealPlayer::create(['name' => $request->name, 'role' => $request->role, 'real_team' => $request->real_team, 'initial_value' => 1]); Roster::create(['league_id' => $league->id, 'user_id' => $request->user_id, 'real_player_id' => $newPlayer->id, 'purchase_price' => $request->price, 'contract_years' => $request->years]); $p = LeagueParticipant::where('league_id', $league->id)->where('user_id', $request->user_id)->first(); if ($p) $p->decrement('remaining_budget', $request->price); return back(); }
    public function assignPlayer(Request $request) { $league = auth()->user()->leagues()->first(); Roster::create(['league_id' => $league->id, 'user_id' => $request->user_id, 'real_player_id' => $request->player_id, 'purchase_price' => $request->price, 'contract_years' => $request->years]); $p = LeagueParticipant::where('league_id', $league->id)->where('user_id', $request->user_id)->first(); if ($p) $p->decrement('remaining_budget', $request->price); return back(); }
    public function removePlayer(Request $request) { $rosterItem = Roster::findOrFail($request->roster_id); $p = LeagueParticipant::where('league_id', $rosterItem->league_id)->where('user_id', $rosterItem->user_id)->first(); if ($p) $p->increment('remaining_budget', $rosterItem->purchase_price); $rosterItem->delete(); return back(); }
    public function create() { return Inertia::render('Leagues/Create'); }
    public function store(Request $request) { $league = League::create(['name' => $request->name, 'invite_code' => strtoupper(Str::random(8)), 'admin_id' => auth()->id(), 'initial_budget' => $request->initial_budget]); LeagueParticipant::create(['league_id' => $league->id, 'user_id' => auth()->id(), 'team_name' => $request->team_name, 'remaining_budget' => $request->initial_budget, 'years_budget' => 40]); return redirect()->route('dashboard'); }
    public function join() { return Inertia::render('Leagues/Join'); }
    public function joinStore(Request $request) { $league = League::where('invite_code', $request->invite_code)->first(); if (!$league) return back()->withErrors(['invite_code' => 'Codice errato']); LeagueParticipant::create(['league_id' => $league->id, 'user_id' => auth()->id(), 'team_name' => $request->team_name, 'remaining_budget' => $league->initial_budget, 'years_budget' => 40]); return redirect()->route('dashboard'); }
    public function toggleMarket(League $league) { if (auth()->id() !== $league->admin_id) return back(); $league->update(['is_market_open' => !$league->is_market_open]); return back(); }
    public function updateMarket(Request $request, League $league) { if (auth()->id() !== $league->admin_id) return back(); $league->update(['market_start_at' => $request->market_start_at, 'market_end_at' => $request->market_end_at]); $league->save(); return back(); }
}