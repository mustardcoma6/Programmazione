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
    public function dashboard()
    {
        $user = auth()->user();
        $leagues = $user->leagues()->get(); 
        $firstLeague = $leagues->first();
        $myData = null; $myPlayers = []; $currentLineup = null; $allParticipants = []; $isMarketOpen = false; $stats = null;

        if ($firstLeague) {
            $myData = LeagueParticipant::where('league_id', $firstLeague->id)->where('user_id', $user->id)->first();
            $myPlayers = Roster::where('league_id', $firstLeague->id)->where('user_id', $user->id)->with('player')->get();
            $currentLineup = Lineup::where('league_id', $firstLeague->id)->where('user_id', $user->id)->where('matchday', 1)->with('details.player')->first();
            $allParticipants = LeagueParticipant::where('league_id', $firstLeague->id)->orderBy('remaining_budget', 'desc')->get();
            $isMarketOpen = MarketSession::where('league_id', $firstLeague->id)->where('start_at', '<=', now())->where('end_at', '>=', now())->exists();

            // Ranking
            $ranking = ['SANTOS' => 49, 'BOTAFOGO' => 44, 'PALMEIRAS' => 43, 'ATLETICO G MINEIRO' => 36, 'VASCO DE GAMA' => 30, 'CORINTHIANS' => 30, 'FLAMENGO' => 26, 'CRUZEIRO E.C.' => 18, 'FLUMINENSE' => 0, 'SAO PAULO' => 0];
            arsort($ranking);
            $sortedPresidents = array_keys($ranking);
            $pos = array_search(strtoupper(trim($user->name)), $sortedPresidents);
            $generalRank = ($pos !== false) ? ($pos + 1) : '-';

            // Stats
            $topSigning = Roster::where('user_id', $user->id)->where('league_id', $firstLeague->id)->with('player')->orderBy('purchase_price', 'desc')->first();
            $creditRank = LeagueParticipant::where('league_id', $firstLeague->id)->where('remaining_budget', '>', $myData->remaining_budget)->count() + 1;

            $stats = ['topPlayer' => $topSigning ? $topSigning->player->name : 'Nessuno', 'topPrice' => $topSigning ? $topSigning->purchase_price : 0, 'rank' => $creditRank, 'generalRank' => $generalRank, 'totalParticipants' => count($allParticipants)];
        }
        return Inertia::render('Dashboard', ['leagues' => $leagues, 'myData' => $myData, 'myPlayers' => $myPlayers, 'currentLineup' => $currentLineup, 'allParticipants' => $allParticipants, 'isMarketOpen' => (bool)$isMarketOpen, 'stats' => $stats]);
    }

    public function rankingIndex()
    {
        $rankingData = [['name' => 'SANTOS', 'points' => 49], ['name' => 'BOTAFOGO', 'points' => 44], ['name' => 'PALMEIRAS', 'points' => 43], ['name' => 'ATLETICO G MINEIRO', 'points' => 36], ['name' => 'VASCO DE GAMA', 'points' => 30], ['name' => 'CORINTHIANS', 'points' => 30], ['name' => 'FLAMENGO', 'points' => 26], ['name' => 'CRUZEIRO E.C.', 'points' => 18], ['name' => 'FLUMINENSE', 'points' => 0], ['name' => 'SAO PAULO', 'points' => 0]];
        return Inertia::render('Lega/Ranking', ['ranking' => $rankingData, 'lastUpdate' => now()->format('d/m/Y')]);
    }

    public function manageFinances() { $l = auth()->user()->leagues()->first(); $p = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); return Inertia::render('Admin/Finances', ['league' => $l, 'participants' => $p, 'stats' => ['totalCredits' => $p->sum('remaining_budget'), 'totalYears' => $p->sum('years_budget'), 'avgCredits' => round($p->avg('remaining_budget'))]]); }
    public function managePrimavera() { $l = auth()->user()->leagues()->first(); $teams = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); foreach ($teams as $t) { $t->primavera_players = PrimaveraRoster::where('league_id', $l->id)->where('user_id', $t->user_id)->with('player')->get(); } $sold = array_merge(Roster::where('league_id', $l->id)->pluck('real_player_id')->toArray(), PrimaveraRoster::where('league_id', $l->id)->pluck('real_player_id')->toArray()); $available = RealPlayer::whereNotIn('id', $sold)->orderBy('role', 'desc')->get(); return Inertia::render('Admin/Primavera', ['league' => $l, 'teams' => $teams, 'availablePlayers' => $available]); }
    public function assignPrimavera(Request $request) { PrimaveraRoster::create(['league_id' => auth()->user()->leagues()->first()->id, 'user_id' => $request->user_id, 'real_player_id' => $request->player_id, 'purchase_price' => $request->price]); $p = LeagueParticipant::where('user_id', $request->user_id)->first(); $p->decrement('remaining_budget', $request->price); return back(); }
    public function removePrimavera(Request $request) { $item = PrimaveraRoster::findOrFail($request->roster_id); $p = LeagueParticipant::where('user_id', $item->user_id)->first(); $p->increment('remaining_budget', $item->purchase_price); $item->delete(); return back(); }
    public function updateResources(Request $request) { $p = LeagueParticipant::findOrFail($request->participant_id); $p->update(['remaining_budget' => $request->new_credits, 'years_budget' => $request->new_years, 'remaining_primavera_budget' => $request->new_primavera_credits]); return back(); }
    public function manageCredits() { $l = auth()->user()->leagues()->first(); $teams = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); return Inertia::render('Admin/Credits', ['league' => $l, 'teams' => $teams]); }
    public function manageRosters() { $l = auth()->user()->leagues()->first(); $teams = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); foreach ($teams as $team) { $team->players = Roster::where('league_id', $l->id)->where('user_id', $team->user_id)->with('player')->get(); } $sold = array_merge(Roster::where('league_id', $l->id)->pluck('real_player_id')->toArray(), PrimaveraRoster::where('league_id', $l->id)->pluck('real_player_id')->toArray()); $avail = RealPlayer::whereNotIn('id', $sold)->orderBy('role', 'desc')->get(); return Inertia::render('Admin/Rosters', ['league' => $l, 'teams' => $teams, 'availablePlayers' => $avail]); }
    public function assignPlayer(Request $request) { $l = auth()->user()->leagues()->first(); Roster::create(['league_id' => $l->id, 'user_id' => $request->user_id, 'real_player_id' => $request->player_id, 'purchase_price' => $request->price, 'contract_years' => $request->years]); $p = LeagueParticipant::where('league_id', $l->id)->where('user_id', $request->user_id)->first(); $p->decrement('remaining_budget', $request->price); return back(); }
    public function assignManualPlayer(Request $request) { $l = auth()->user()->leagues()->first(); $np = RealPlayer::create(['name' => $request->name, 'role' => $request->role, 'real_team' => $request->real_team, 'initial_value' => 1, 'nationality' => $request->nationality ?? 'Italia']); Roster::create(['league_id' => $l->id, 'user_id' => $request->user_id, 'real_player_id' => $np->id, 'purchase_price' => $request->price, 'contract_years' => $request->years]); $p = LeagueParticipant::where('user_id', $request->user_id)->first(); if($p) $p->decrement('remaining_budget', $request->price); return back(); }
    public function removePlayer(Request $request) { $r = Roster::findOrFail($request->roster_id); $p = LeagueParticipant::where('user_id', $r->user_id)->first(); $p->increment('remaining_budget', $r->purchase_price); $r->delete(); return back(); }
    public function societaIndex() { $l = auth()->user()->leagues()->first(); $parts = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); foreach ($parts as $p) { $p->years_used = Roster::where('league_id', $l->id)->where('user_id', $p->user_id)->sum('contract_years'); } return Inertia::render('Societa/Index', ['league' => $l, 'participants' => $parts]); }
    public function store(Request $request) { $l = League::create(['name' => $request->name, 'invite_code' => strtoupper(Str::random(8)), 'admin_id' => auth()->id(), 'initial_budget' => $request->initial_budget]); LeagueParticipant::create(['league_id' => $l->id, 'user_id' => auth()->id(), 'team_name' => $request->team_name, 'remaining_budget' => $request->initial_budget, 'years_budget' => 40]); return redirect()->route('dashboard'); }
    public function joinStore(Request $request) { $l = League::where('invite_code', $request->invite_code)->first(); LeagueParticipant::create(['league_id' => $l->id, 'user_id' => auth()->id(), 'team_name' => $request->team_name, 'remaining_budget' => $l->initial_budget, 'years_budget' => 40]); return redirect()->route('dashboard'); }
    public function create() { return Inertia::render('Leagues/Create'); }
    public function join() { return Inertia::render('Leagues/Join'); }
    public function updateMarket(Request $request, League $league) { $league->update(['market_start_at' => $request->market_start_at, 'market_end_at' => $request->market_end_at]); $league->save(); return back(); }
    public function kickParticipant(LeagueParticipant $participant)
    {
        $user = auth()->user();
        
        // Carichiamo la lega associata al partecipante
        $league = League::find($participant->league_id);

        // Controllo di sicurezza: se la lega non esiste o non sei l'admin, blocca
        if (!$league || $league->admin_id !== $user->id) {
            return back()->withErrors(['error' => 'Azione non autorizzata o lega non trovata.']);
        }

        // Impedisci all'admin di espellere se stesso
        if ($participant->user_id === $user->id) {
            return back()->withErrors(['error' => 'Non puoi espellere te stesso.']);
        }

        DB::transaction(function () use ($participant, $league) {
            // 1. Rimuoviamo i suoi calciatori
            Roster::where('league_id', $league->id)
                ->where('user_id', $participant->user_id)
                ->delete();

            // 2. Rimuoviamo le sue formazioni
            $lineupIds = Lineup::where('league_id', $league->id)
                ->where('user_id', $participant->user_id)
                ->pluck('id');
            LineupDetail::whereIn('lineup_id', $lineupIds)->delete();
            Lineup::whereIn('id', $lineupIds)->delete();

            // 3. Rimuoviamo la sua partecipazione
            $participant->delete();
        });

        return back()->with('message', 'Squadra espulsa correttamente.');
    }
}