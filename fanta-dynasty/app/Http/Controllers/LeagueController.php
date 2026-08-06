<?php

namespace App\Http\Controllers;

use App\Models\{League, LeagueParticipant, Roster, PrimaveraRoster, RealPlayer, User, MarketSession, Auction, Lineup, LineupDetail};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeagueController extends Controller
{
    public function rankingIndex()
    {
        $rankingData = [
            ['name' => 'SAO PAULO', 'points' => 51], ['name' => 'SANTOS', 'points' => 49],
            ['name' => 'BOTAFOGO', 'points' => 44], ['name' => 'PALMEIRAS', 'points' => 43],
            ['name' => 'ATLETICO G MINEIRO', 'points' => 36], ['name' => 'VASCO DE GAMA', 'points' => 30],
            ['name' => 'CORINTHIANS', 'points' => 30], ['name' => 'FLAMENGO', 'points' => 26],
            ['name' => 'FLUMINENSE', 'points' => 18], ['name' => 'CRUZEIRO E.C.', 'points' => 17],
        ];
        return Inertia::render('Lega/Ranking', ['ranking' => $rankingData]);
    }

    public function manageFinances()
    {
        $league = auth()->user()->leagues()->first();
        $participants = LeagueParticipant::where('league_id', $league->id)->with('user')->get();
        return Inertia::render('Admin/Finances', [
            'league' => $league, 'participants' => $participants,
            'stats' => ['totalCredits' => $participants->sum('remaining_budget'), 'totalYears' => $participants->sum('years_budget'), 'avgCredits' => round($participants->avg('remaining_budget'))]
        ]);
    }

    public function managePrimavera()
    {
        $league = auth()->user()->leagues()->first();
        $teams = LeagueParticipant::where('league_id', $league->id)->with('user')->get();
        foreach ($teams as $t) { $t->primavera_players = PrimaveraRoster::where('league_id', $league->id)->where('user_id', $t->user_id)->with('player')->get(); }
        $available = RealPlayer::whereNotIn('id', array_merge(Roster::where('league_id', $league->id)->pluck('real_player_id')->toArray(), PrimaveraRoster::where('league_id', $league->id)->pluck('real_player_id')->toArray()))->orderBy('role', 'desc')->get();
        return Inertia::render('Admin/Primavera', ['league' => $league, 'teams' => $teams, 'availablePlayers' => $available]);
    }

    public function assignPrimavera(Request $request) { PrimaveraRoster::create(['league_id' => auth()->user()->leagues()->first()->id, 'user_id' => $request->user_id, 'real_player_id' => $request->player_id, 'purchase_price' => $request->price]); $p = LeagueParticipant::where('user_id', $request->user_id)->first(); $p->decrement('remaining_budget', $request->price); return back(); }
    public function removePrimavera(Request $request) { $item = PrimaveraRoster::findOrFail($request->roster_id); $p = LeagueParticipant::where('user_id', $item->user_id)->first(); $p->increment('remaining_budget', $item->purchase_price); $item->delete(); return back(); }
    public function updateResources(Request $request) { $p = LeagueParticipant::findOrFail($request->participant_id); $p->update(['remaining_budget' => $request->new_credits, 'years_budget' => $request->new_years, 'remaining_primavera_budget' => $request->new_primavera_credits]); return back(); }
    public function manageCredits() { $l = auth()->user()->leagues()->first(); $teams = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); return Inertia::render('Admin/Credits', ['league' => $l, 'teams' => $teams]); }
    public function manageRosters() { $l = auth()->user()->leagues()->first(); $teams = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); foreach ($teams as $team) { $team->players = Roster::where('league_id', $l->id)->where('user_id', $team->user_id)->with('player')->get(); } $sold = array_merge(Roster::where('league_id', $l->id)->pluck('real_player_id')->toArray(), PrimaveraRoster::where('league_id', $l->id)->pluck('real_player_id')->toArray()); $avail = RealPlayer::whereNotIn('id', $sold)->orderBy('role', 'desc')->get(); return Inertia::render('Admin/Rosters', ['league' => $l, 'teams' => $teams, 'availablePlayers' => $avail]); }
    public function assignPlayer(Request $request) { $l = auth()->user()->leagues()->first(); Roster::create(['league_id' => $l->id, 'user_id' => $request->user_id, 'real_player_id' => $request->player_id, 'purchase_price' => $request->price, 'contract_years' => $request->years]); $p = LeagueParticipant::where('user_id', $request->user_id)->first(); $p->decrement('remaining_budget', $request->price); return back(); }
    public function assignManualPlayer(Request $request) { $l = auth()->user()->leagues()->first(); $np = RealPlayer::create(['name' => $request->name, 'role' => $request->role, 'real_team' => $request->real_team, 'initial_value' => 1]); Roster::create(['league_id' => $l->id, 'user_id' => $request->user_id, 'real_player_id' => $np->id, 'purchase_price' => $request->price, 'contract_years' => $request->years]); $p = LeagueParticipant::where('user_id', $request->user_id)->first(); if($p) $p->decrement('remaining_budget', $request->price); return back(); }
    public function removePlayer(Request $request) { $r = Roster::findOrFail($request->roster_id); $p = LeagueParticipant::where('user_id', $r->user_id)->first(); $p->increment('remaining_budget', $r->purchase_price); $r->delete(); return back(); }
    public function societaIndex() { $l = auth()->user()->leagues()->first(); $parts = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); foreach ($parts as $p) { $p->years_used = Roster::where('league_id', $l->id)->where('user_id', $p->user_id)->sum('contract_years'); } return Inertia::render('Societa/Index', ['league' => $l, 'participants' => $parts]); }
    public function store(Request $request) { $l = League::create(['name' => $request->name, 'invite_code' => strtoupper(Str::random(8)), 'admin_id' => auth()->id(), 'initial_budget' => $request->initial_budget]); LeagueParticipant::create(['league_id' => $l->id, 'user_id' => auth()->id(), 'team_name' => $request->team_name, 'remaining_budget' => $request->initial_budget, 'years_budget' => 40]); return redirect()->route('dashboard'); }
    public function joinStore(Request $request) { $l = League::where('invite_code', $request->invite_code)->first(); LeagueParticipant::create(['league_id' => $l->id, 'user_id' => auth()->id(), 'team_name' => $request->team_name, 'remaining_budget' => $l->initial_budget, 'years_budget' => 40]); return redirect()->route('dashboard'); }
    public function create() { return Inertia::render('Leagues/Create'); }
    public function join() { return Inertia::render('Leagues/Join'); }
    public function updateMarket(Request $request, League $league) { $league->update(['market_start_at' => $request->market_start_at, 'market_end_at' => $request->market_end_at]); $league->save(); return back(); }
    public function kickParticipant(LeagueParticipant $p) { $l = League::find($p->league_id); if($l->admin_id !== auth()->id()) return back(); DB::transaction(function() use($p,$l){ Roster::where('league_id', $l->id)->where('user_id', $p->user_id)->delete(); $p->delete(); }); return back(); }
}