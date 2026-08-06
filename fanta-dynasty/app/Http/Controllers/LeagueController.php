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
    // --- 1. LA HOME (DASHBOARD) ---
    public function dashboard()
    {
        $user = auth()->user();
        $leagues = $user->leagues()->get(); 
        $firstLeague = $leagues->first();
        
        $myData = null; $myPlayers = []; $currentLineup = null; $allParticipants = []; 
        $isMarketOpen = false; $stats = null;

        if ($firstLeague) {
            $myData = LeagueParticipant::where('league_id', $firstLeague->id)->where('user_id', $user->id)->first();
            $myPlayers = Roster::where('league_id', $firstLeague->id)->where('user_id', $user->id)->with('player')->get();
            $currentLineup = Lineup::where('league_id', $firstLeague->id)->where('user_id', $user->id)->where('matchday', 1)->with('details.player')->first();
            $allParticipants = LeagueParticipant::where('league_id', $firstLeague->id)->orderBy('remaining_budget', 'desc')->get();
            
            // Stato Mercato
            $isMarketOpen = MarketSession::where('league_id', $firstLeague->id)->where('start_at', '<=', now())->where('end_at', '>=', now())->exists();

            // Statistiche
            $topSigning = Roster::where('user_id', $user->id)->where('league_id', $firstLeague->id)->with('player')->orderBy('purchase_price', 'desc')->first();
            $rank = LeagueParticipant::where('league_id', $firstLeague->id)->where('remaining_budget', '>', $myData->remaining_budget)->count() + 1;

            $stats = [
                'topPlayer' => $topSigning ? $topSigning->player->name : 'Nessuno',
                'topPrice' => $topSigning ? $topSigning->purchase_price : 0,
                'rank' => $rank,
                'totalParticipants' => count($allParticipants)
            ];
        }

        return Inertia::render('Dashboard', [
            'leagues' => $leagues,
            'myData' => $myData,
            'myPlayers' => $myPlayers,
            'currentLineup' => $currentLineup,
            'allParticipants' => $allParticipants,
            'isMarketOpen' => (bool)$isMarketOpen,
            'stats' => $stats
        ]);
    }

    // --- 2. GESTIONE PRIMAVERA (ADMIN) ---
    public function managePrimavera()
    {
        $user = auth()->user();
        $league = League::where('admin_id', $user->id)->first();
        if (!$league) return redirect()->route('dashboard');

        $teams = LeagueParticipant::where('league_id', $league->id)->with('user')->get();
        foreach ($teams as $team) {
            $team->primavera_players = PrimaveraRoster::where('league_id', $league->id)->where('user_id', $team->user_id)->with('player')->get();
        }

        $soldIds = Roster::where('league_id', $league->id)->pluck('real_player_id')->toArray();
        $primaveraIds = PrimaveraRoster::where('league_id', $league->id)->pluck('real_player_id')->toArray();
        $availablePlayers = RealPlayer::whereNotIn('id', array_merge($soldIds, $primaveraIds))->orderBy('role', 'desc')->get();

        return Inertia::render('Admin/Primavera', [
            'league' => $league,
            'teams' => $teams,
            'availablePlayers' => $availablePlayers
        ]);
    }

    public function assignPrimavera(Request $request)
    {
        $request->validate(['player_id' => 'required', 'user_id' => 'required', 'price' => 'required|integer']);
        $league = auth()->user()->leagues()->first();

        PrimaveraRoster::create([
            'league_id' => $league->id,
            'user_id' => $request->user_id,
            'real_player_id' => $request->player_id,
            'purchase_price' => $request->price
        ]);

        $p = LeagueParticipant::where('league_id', $league->id)->where('user_id', $request->user_id)->first();
        $p->decrement('remaining_budget', $request->price);

        return back();
    }

    public function removePrimavera(Request $request)
    {
        $item = PrimaveraRoster::findOrFail($request->roster_id);
        $p = LeagueParticipant::where('league_id', $item->league_id)->where('user_id', $item->user_id)->first();
        $p->increment('remaining_budget', $item->purchase_price);
        $item->delete();
        return back();
    }

    // --- 3. TESORERIA (CREDITI E ANNI) ---
    public function manageCredits()
    {
        $user = auth()->user();
        $league = League::where('admin_id', $user->id)->first();
        if (!$league) return redirect()->route('dashboard');
        $teams = LeagueParticipant::where('league_id', $league->id)->with('user')->get();
        return Inertia::render('Admin/Credits', ['league' => $league, 'teams' => $teams]);
    }

    public function updateResources(Request $request)
    {
        $request->validate([
            'participant_id' => 'required|exists:league_participants,id',
            'new_credits' => 'required|integer',
            'new_years' => 'required|integer',
            'new_primavera_credits' => 'required|integer' // Ricevuto dal modulo
        ]);
        $p = LeagueParticipant::findOrFail($request->participant_id);
        $p->update([
            'remaining_budget' => $request->new_credits,
            'years_budget' => $request->new_years,
            'remaining_primavera_budget' => $request->new_primavera_credits
        ]);
        return back();
    }

    // --- 4. GESTIONE ROSE (ADMIN) ---
    public function manageRosters()
    {
        $user = auth()->user();
        $league = League::where('admin_id', $user->id)->first();
        if (!$league) return redirect()->route('dashboard');

        $teams = LeagueParticipant::where('league_id', $league->id)->with('user')->get();
        foreach ($teams as $team) {
            $team->players = Roster::where('league_id', $league->id)->where('user_id', $team->user_id)->with('player')->get();
        }

        $soldIds = Roster::where('league_id', $league->id)->pluck('real_player_id')->toArray();
        $availablePlayers = RealPlayer::whereNotIn('id', $soldIds)->orderBy('role', 'desc')->get();

        return Inertia::render('Admin/Rosters', ['league' => $league, 'teams' => $teams, 'availablePlayers' => $availablePlayers]);
    }

    // --- 5. FUNZIONI LEGA ---
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

    public function store(Request $request) {
        $league = League::create(['name' => $request->name, 'invite_code' => strtoupper(Str::random(8)), 'admin_id' => auth()->id(), 'initial_budget' => $request->initial_budget]);
        LeagueParticipant::create(['league_id' => $league->id, 'user_id' => auth()->id(), 'team_name' => $request->team_name, 'remaining_budget' => $request->initial_budget, 'years_budget' => 40]);
        return redirect()->route('dashboard');
    }

    public function joinStore(Request $request) {
        $league = League::where('invite_code', $request->invite_code)->first();
        if (!$league) return back()->withErrors(['invite_code' => 'Codice errato']);
        LeagueParticipant::create(['league_id' => $league->id, 'user_id' => auth()->id(), 'team_name' => $request->team_name, 'remaining_budget' => $league->initial_budget, 'years_budget' => 40]);
        return redirect()->route('dashboard');
    }

    public function create() { return Inertia::render('Leagues/Create'); }
    public function join() { return Inertia::render('Leagues/Join'); }
    public function updateMarket(Request $request, League $league) { $league->update(['market_start_at' => $request->market_start_at, 'market_end_at' => $request->market_end_at]); $league->save(); return back(); }
    public function assignPlayer(Request $request) { $league = auth()->user()->leagues()->first(); Roster::create(['league_id' => $league->id, 'user_id' => $request->user_id, 'real_player_id' => $request->player_id, 'purchase_price' => $request->price, 'contract_years' => $request->years]); $p = LeagueParticipant::where('league_id', $league->id)->where('user_id', $request->user_id)->first(); $p->decrement('remaining_budget', $request->price); return back(); }
    public function removePlayer(Request $request) { $rosterItem = Roster::findOrFail($request->roster_id); $p = LeagueParticipant::where('league_id', $rosterItem->league_id)->where('user_id', $rosterItem->user_id)->first(); $p->increment('remaining_budget', $rosterItem->purchase_price); $rosterItem->delete(); return back(); }
    public function kickParticipant(LeagueParticipant $p) { $league = League::find($p->league_id); if ($league->admin_id !== auth()->id()) return back(); DB::transaction(function () use ($p, $league) { Roster::where('league_id', $league->id)->where('user_id', $p->user_id)->delete(); $p->delete(); }); return back(); }
    public function assignManualPlayer(Request $request) { $league = auth()->user()->leagues()->first(); $newPlayer = RealPlayer::create(['name' => $request->name, 'role' => $request->role, 'real_team' => $request->real_team, 'initial_value' => 1]); Roster::create(['league_id' => $league->id, 'user_id' => $request->user_id, 'real_player_id' => $newPlayer->id, 'purchase_price' => $request->price, 'contract_years' => $request->years]); $p = LeagueParticipant::where('league_id', $league->id)->where('user_id', $request->user_id)->first(); if ($p) $p->decrement('remaining_budget', $request->price); return back(); }
    public function rankingIndex()
    {
        $user = auth()->user();
        $league = $user->leagues()->first();
        if (!$league) return redirect()->route('dashboard');

        // Dati statici forniti per la classifica attuale
        $rankingData = [
            ['name' => 'SAO PAULO', 'points' => 51],
            ['name' => 'SANTOS', 'points' => 49],
            ['name' => 'BOTAFOGO', 'points' => 44],
            ['name' => 'PALMEIRAS', 'points' => 43],
            ['name' => 'ATLETICO G MINEIRO', 'points' => 36],
            ['name' => 'VASCO DE GAMA', 'points' => 30],
            ['name' => 'CORINTHIANS', 'points' => 30],
            ['name' => 'FLAMENGO', 'points' => 26],
            ['name' => 'FLUMINENSE', 'points' => 18],
            ['name' => 'CRUZEIRO E.C.', 'points' => 17],
        ];

        return Inertia::render('Lega/Ranking', [
            'ranking' => $rankingData,
            'leagueName' => $league->name
        ]);
    }
}