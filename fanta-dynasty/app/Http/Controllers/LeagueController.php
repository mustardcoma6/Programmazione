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
            $myPlayers = Roster::where('league_id', $firstLeague->id)->where('user_id', $user->id)->with('player')->get();
            $allParticipants = LeagueParticipant::where('league_id', $firstLeague->id)->get();
            
            // Classifica Campionato
            $classifica = LeagueParticipant::where('league_id', $firstLeague->id)
                ->with('user')
                ->orderBy('league_points', 'desc')
                ->orderBy('total_points', 'desc')
                ->get();

            // Calcolo Posizione Classifica
            $posClassifica = $classifica->search(fn($i) => $i->user_id === $user->id);
            $generalRank = ($posClassifica !== false) ? ($posClassifica + 1) : '-';

            // Calcolo Posizione Crediti
            $creditRank = LeagueParticipant::where('league_id', $firstLeague->id)
                ->where('remaining_budget', '>', $myData->remaining_budget)
                ->count() + 1;

            // LOGICA TOP PLAYER (Ripristinata!)
            $topSigning = Roster::where('user_id', $user->id)
                ->where('league_id', $firstLeague->id)
                ->with('player')
                ->orderBy('purchase_price', 'desc')
                ->first();

            $stats = [
                'generalRank' => $generalRank,
                'rank' => $creditRank,
                'topPlayer' => $topSigning ? $topSigning->player->name : 'Nessuno',
                'topPrice' => $topSigning ? $topSigning->purchase_price : 0,
            ];
        }

        return Inertia::render('Dashboard', [
            'leagues' => $leagues, 
            'myData' => $myData, 
            'myPlayers' => $myPlayers, 
            'allParticipants' => $allParticipants, 
            'stats' => $stats, 
            'classifica' => $classifica 
        ]);
    }

    public function editCampionato() 
    {
        $l = auth()->user()->leagues()->first();
        $participants = LeagueParticipant::where('league_id', $l->id)->with('user')->get();
        return Inertia::render('Admin/CampionatoEdit', ['participants' => $participants]);
    }

    public function updateCampionato(Request $request) 
   {
    // 1. Definiamo i parametri base per il calcolo del valore
    $investimentoBase = 130;
    $plusvalorePotenziale = 440; // (570 - 130)

    // 2. Calcoliamo il Benchmark (Rosa perfetta del listone)
    $topP = \App\Models\RealPlayer::where('role', 'P')->orderBy('quotation', 'desc')->limit(3)->get()->sum('quotation');
    $topD = \App\Models\RealPlayer::where('role', 'D')->orderBy('quotation', 'desc')->limit(8)->get()->sum('quotation');
    $topC = \App\Models\RealPlayer::where('role', 'C')->orderBy('quotation', 'desc')->limit(8)->get()->sum('quotation');
    $topA = \App\Models\RealPlayer::where('role', 'A')->orderBy('quotation', 'desc')->limit(6)->get()->sum('quotation');
    $benchmarkQuotazione = ($topP + $topD + $topC + $topA) ?: 1;

    // 3. Funzione per calcolare i gol in base ai punti (tua scala)
    $calcolaGol = function($p) {
        if ($p < 66) return 0;
        if ($p < 70) return 1;
        return 2 + floor(($p - 70) / 5);
    };

    // 4. AGGIORNIAMO I DATI DELLE SQUADRE NEL DATABASE
    foreach ($request->classifica as $data) {
        $lp = \App\Models\LeagueParticipant::find($data['id']);
        if ($lp) {
            // Assegnazione manuale dei valori per essere sicuri
            $lp->league_points = $data['league_points'];
            $lp->total_points = $data['total_points'];
            $lp->games_played = $data['games_played'];
            
            // Forza il salvataggio sul database
            $lp->save();
        }
    }

    // 5. ORA RICALCOLIAMO IL VALORE DI MERCATO PER TUTTI E SALVIAMO NELLA STORIA
    // Recuperiamo tutti i partecipanti aggiornati della lega
    $firstId = $request->classifica[0]['id'];
    $leagueId = \App\Models\LeagueParticipant::find($firstId)->league_id;
    $tutti = \App\Models\LeagueParticipant::where('league_id', $leagueId)->get();

    // Troviamo chi segna più di tutti per l'efficienza
    $maxGolLega = $tutti->map(fn($s) => $calcolaGol($s->games_played > 0 ? ($s->total_points / $s->games_played) : 0))->max() ?: 1;

    foreach ($tutti as $lp) {
        // A. Calcolo Asset Quality (Rosa attuale)
        $rosterSum = \App\Models\Roster::where('user_id', $lp->user_id)->where('league_id', $lp->league_id)
            ->with('player')->get()->sum(fn($r) => $r->player->quotation ?? 0);
        $assetQuality = $rosterSum / $benchmarkQuotazione;

        // B. Calcolo Winning Efficiency (Gol rispetto al leader)
        $mediaP = $lp->games_played > 0 ? ($lp->total_points / $lp->games_played) : 0;
        $tuoiGol = $calcolaGol($mediaP);
        $winningEfficiency = $tuoiGol / $maxGolLega;

        // C. VALORE ATTUALE (Ecco la variabile che mancava!)
        $valoreAttuale = $investimentoBase + ($plusvalorePotenziale * $assetQuality * $winningEfficiency);

        // D. Salva nella tabella dei grafici
        if ($lp->games_played > 0) {
            \App\Models\MarketValueHistory::updateOrCreate(
                [
                    'user_id' => $lp->user_id,
                    'league_id' => $lp->league_id,
                    'matchday' => $lp->games_played
                ],
                [
                    'value' => round($valoreAttuale, 2),
                    'recorded_at' => now()
                ]
            );
        }
    }

    return redirect()->back()->with('message', 'Classifica e Valutazioni aggiornate correttamente!');
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