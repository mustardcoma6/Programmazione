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
        $classifica = []; 

        if ($firstLeague) {
            $myData = LeagueParticipant::where('league_id', $firstLeague->id)->where('user_id', $user->id)->first();
            
            $classifica = LeagueParticipant::where('league_id', $firstLeague->id)
                ->with('user')
                ->orderBy('league_points', 'desc')
                ->orderBy('total_points', 'desc')
                ->get();

            // --- CALCOLO VALORI FINANZIARI PER LA HOME ---
            $topP = RealPlayer::where('role', 'P')->orderBy('quotation', 'desc')->limit(3)->get()->sum('quotation');
            $topD = RealPlayer::where('role', 'D')->orderBy('quotation', 'desc')->limit(8)->get()->sum('quotation');
            $topC = RealPlayer::where('role', 'C')->orderBy('quotation', 'desc')->limit(8)->get()->sum('quotation');
            $topA = RealPlayer::where('role', 'A')->orderBy('quotation', 'desc')->limit(6)->get()->sum('quotation');
            $benchmark = ($topP + $topD + $topC + $topA) ?: 1;

            $calcolaGol = function($p) {
                if ($p < 66) return 0;
                if ($p < 70) return 1;
                return 2 + floor(($p - 70) / 5);
            };

            $tutti = LeagueParticipant::where('league_id', $firstLeague->id)->get();
            $maxGolLega = $tutti->map(fn($s) => $calcolaGol($s->games_played > 0 ? ($s->total_points / $s->games_played) : 0))->max() ?: 1;

            // Dati specifici dell'utente
            $rosterSum = Roster::where('user_id', $user->id)->where('league_id', $firstLeague->id)->with('player')->get()->sum(fn($r) => $r->player->quotation ?? 0);
            $assetQual = $rosterSum / $benchmark;
            $mediaP = $myData->games_played > 0 ? ($myData->total_points / $myData->games_played) : 0;
            $winEff = $calcolaGol($mediaP) / $maxGolLega;

            // Valore Monetario (stessa formula della pagina finanze)
            $valoreAttuale = 130 + (440 * $assetQual * $winEff);
            
            // Probabilità di Vincita (Asset Quality * Efficiency)
            $probabilitaVittoria = ($assetQual * $winEff) * 100;

            // --- FINE CALCOLI ---

            $posClassifica = $classifica->search(fn($i) => $i->user_id === $user->id);
            $generalRank = ($posClassifica !== false) ? ($posClassifica + 1) : '-';

            $creditRank = LeagueParticipant::where('league_id', $firstLeague->id)
                ->where('remaining_budget', '>', $myData->remaining_budget)
                ->count() + 1;

            $topSigning = Roster::where('user_id', $user->id)->where('league_id', $firstLeague->id)->with('player')->orderBy('purchase_price', 'desc')->first();

            $stats = [
                'generalRank' => $generalRank,
                'rank' => $creditRank,
                'topPlayer' => $topSigning ? $topSigning->player->name : 'Nessuno',
                'topPrice' => $topSigning ? $topSigning->purchase_price : 0,
                'valore_societario' => round($valoreAttuale, 2),
                'probabilita_vittoria' => round($probabilitaVittoria, 1)
            ];
        }

    public function editCampionato() 
    {
        $l = auth()->user()->leagues()->first();
        $participants = LeagueParticipant::where('league_id', $l->id)->with('user')->get();
        return Inertia::render('Admin/CampionatoEdit', ['participants' => $participants]);
    }

    public function updateCampionato(Request $request) 
    {
    // 1. Parametri per il valore monetario
    $investimentoBase = 130;
    $plusvalorePotenziale = 440;

    $calcolaGol = function($p) {
        if ($p < 66) return 0;
        if ($p < 70) return 1;
        return 2 + floor(($p - 70) / 5);
    };

    // 2. AGGIORNAMENTO DATI SQUADRE (Versione Esplicita)
    foreach ($request->classifica as $data) {
        $lp = \App\Models\LeagueParticipant::find($data['id']);
        if ($lp) {
            // Assegniamo i valori uno per uno per bypassare ogni blocco
            $lp->league_points = $data['league_points'];
            $lp->total_points  = $data['total_points'];
            $lp->games_played  = $data['games_played'];
            
            // Salvataggio forzato
            $lp->save();
        }
    }

    // 3. RICALCOLO BENCHMARK (Top 25 Listone)
    $topP = \App\Models\RealPlayer::where('role', 'P')->orderBy('quotation', 'desc')->limit(3)->get()->sum('quotation');
    $topD = \App\Models\RealPlayer::where('role', 'D')->orderBy('quotation', 'desc')->limit(8)->get()->sum('quotation');
    $topC = \App\Models\RealPlayer::where('role', 'C')->orderBy('quotation', 'desc')->limit(8)->get()->sum('quotation');
    $topA = \App\Models\RealPlayer::where('role', 'A')->orderBy('quotation', 'desc')->limit(6)->get()->sum('quotation');
    $benchmarkVal = ($topP + $topD + $topC + $topA) ?: 1;

    // 4. TROVA LEADER GOL DELLA LEGA (Dopo gli aggiornamenti)
    $firstTeam = \App\Models\LeagueParticipant::find($request->classifica[0]['id']);
    $tutti = \App\Models\LeagueParticipant::where('league_id', $firstTeam->league_id)->get();
    $maxGolLega = $tutti->map(fn($s) => $calcolaGol($s->games_played > 0 ? ($s->total_points / $s->games_played) : 0))->max() ?: 1;

    // 5. SALVATAGGIO STORIA E PULIZIA "ORFANI"
    foreach ($tutti as $s) {
        // Calcolo valore attuale
        $rosterSum = \App\Models\Roster::where('user_id', $s->user_id)->where('league_id', $s->league_id)
            ->with('player')->get()->sum(fn($r) => $r->player->quotation ?? 0);
        $assetQual = $rosterSum / $benchmarkVal;
        $mediaP = $s->games_played > 0 ? ($s->total_points / $s->games_played) : 0;
        $winEff = $calcolaGol($mediaP) / $maxGolLega;

        $valoreAttuale = round($investimentoBase + ($plusvalorePotenziale * $assetQual * $winEff), 2);

        if ($s->games_played > 0) {
            // Salva il punto attuale
            \App\Models\MarketValueHistory::updateOrCreate(
                ['user_id' => $s->user_id, 'league_id' => $s->league_id, 'matchday' => $s->games_played],
                ['value' => $valoreAttuale, 'recorded_at' => now()]
            );

            // CANCELLA TUTTI I PUNTI FUTURI (Se sei tornato indietro con le giornate)
            \App\Models\MarketValueHistory::where('user_id', $s->user_id)
                ->where('league_id', $s->league_id)
                ->where('matchday', '>', $s->games_played)
                ->delete();
        } else {
            // Se le partite sono 0, pulisci tutto per quella squadra
            \App\Models\MarketValueHistory::where('user_id', $s->user_id)->delete();
        }
    }

    return redirect()->back()->with('message', 'Classifica aggiornata e cronologia sincronizzata!');
    }

    public function rankingIndex()
    {
        $rankingData = [
            ['name' => 'SANTOS', 'points' => 49], ['name' => 'BOTAFOGO', 'points' => 44], 
            ['name' => 'PALMEIRAS', 'points' => 43], ['name' => 'ATLETICO G MINEIRO', 'points' => 36], 
            ['name' => 'VASCO DE GAMA', 'points' => 30], ['name' => 'CORINTHIANS', 'points' => 30], 
            ['name' => 'FLAMENGO', 'points' => 26], ['name' => 'CRUZEIRO E.C.', 'points' => 18], 
            ['name' => 'FLUMINENSE', 'points' => 0], ['name' => 'SAO PAULO', 'points' => 0]
        ];
        return Inertia::render('Lega/Ranking', ['ranking' => $rankingData, 'lastUpdate' => '27/08/2024']);
    }

    // Altri metodi (rimangono invariati)
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
        $league = League::find($participant->league_id);
        if (!$league || $league->admin_id !== $user->id) return back()->withErrors(['error' => 'Azione non autorizzata.']);
        if ($participant->user_id === $user->id) return back()->withErrors(['error' => 'Non puoi espellere te stesso.']);

        DB::transaction(function () use ($participant, $league) {
            Roster::where('league_id', $league->id)->where('user_id', $participant->user_id)->delete();
            $lineupIds = Lineup::where('league_id', $league->id)->where('user_id', $participant->user_id)->pluck('id');
            LineupDetail::whereIn('lineup_id', $lineupIds)->delete();
            Lineup::whereIn('id', $lineupIds)->delete();
            $participant->delete();
        });
        return back()->with('message', 'Squadra espulsa.');
    }
}