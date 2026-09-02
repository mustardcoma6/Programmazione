<?php

namespace App\Http\Controllers;

use App\Models\{League, LeagueParticipant, Roster, PrimaveraRoster, RealPlayer, User, MarketSession, MarketValueHistory, Lineup, LineupDetail};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class LeagueController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $leagues = $user->leagues()->get(); 
        $firstLeague = $leagues->first();
        
        $myData = null; 
        $stats = null; 
        $classifica = []; 
        $allTeamsValues = [];

        if ($firstLeague) {
            $myData = LeagueParticipant::where('league_id', $firstLeague->id)->where('user_id', $user->id)->first();
            
            // 1. CLASSIFICA CAMPIONATO
            $classifica = LeagueParticipant::where('league_id', $firstLeague->id)
                ->with('user')
                ->orderBy('league_points', 'desc')
                ->orderBy('total_points', 'desc')
                ->get();

            // 2. PARAMETRI PER CALCOLO FINANZIARIO (Top 25 per Ruolo)
            $topP = RealPlayer::where('role', 'P')->orderBy('quotation', 'desc')->limit(3)->get()->sum('quotation');
            $topD = RealPlayer::where('role', 'D')->orderBy('quotation', 'desc')->limit(8)->get()->sum('quotation');
            $topC = RealPlayer::where('role', 'C')->orderBy('quotation', 'desc')->limit(8)->get()->sum('quotation');
            $topA = RealPlayer::where('role', 'A')->orderBy('quotation', 'desc')->limit(6)->get()->sum('quotation');
            $benchmarkVal = ($topP + $topD + $topC + $topA) ?: 1;

            $calcolaGol = function($p) {
                if ($p < 66) return 0;
                if ($p < 70) return 1;
                return 2 + floor(($p - 70) / 5);
            };

            $tutti = LeagueParticipant::where('league_id', $firstLeague->id)->get();
            $maxGolLega = $tutti->map(fn($s) => $calcolaGol($s->games_played > 0 ? ($s->total_points / $s->games_played) : 0))->max() ?: 1;

            // 3. CALCOLO VALORI E TREND PER TUTTE LE SQUADRE
            foreach ($tutti as $s) {
                $rSum = Roster::where('user_id', $s->user_id)->where('league_id', $s->league_id)
                    ->with('player')->get()->sum(fn($r) => $r->player->quotation ?? 0);
                $aQ = $rSum / $benchmarkVal;
                $mP = $s->games_played > 0 ? ($s->total_points / $s->games_played) : 0;
                $wE = $calcolaGol($mP) / $maxGolLega;

                $valoreAttuale = 130 + (440 * $aQ * $wE);

                $prev = MarketValueHistory::where('user_id', $s->user_id)
                    ->where('league_id', $firstLeague->id)
                    ->where('matchday', '<', $s->games_played)
                    ->orderBy('matchday', 'desc')
                    ->first();
                
                $trend = ['dir' => 'stable', 'perc' => 0];
                if ($prev && $prev->value > 0) {
                    $diff = $valoreAttuale - $prev->value;
                    $trend = [
                        'dir' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'stable'),
                        'perc' => round(abs(($diff / $prev->value) * 100), 1)
                    ];
                }

                $allTeamsValues[] = [
                    'user_id' => $s->user_id,
                    'team_name' => $s->team_name,
                    'valore' => round($valoreAttuale, 2),
                    'trend' => $trend
                ];
            }

            usort($allTeamsValues, fn($a, $b) => $b['valore'] <=> $a['valore']);

            // 4. STATISTICHE UTENTE LOGGATO
            $mioDatoFin = collect($allTeamsValues)->firstWhere('user_id', $user->id);
            $posCampionato = $classifica->search(fn($i) => $i->user_id === $user->id);
            $topSigning = Roster::where('user_id', $user->id)->where('league_id', $firstLeague->id)
                ->with('player')->orderBy('purchase_price', 'desc')->first();

            $stats = [
                'generalRank' => ($posCampionato !== false) ? ($posCampionato + 1) : '-',
                'rank' => LeagueParticipant::where('league_id', $firstLeague->id)->where('remaining_budget', '>', $myData->remaining_budget)->count() + 1,
                'topPlayer' => $topSigning ? $topSigning->player->name : 'Nessuno',
                'topPrice' => $topSigning ? $topSigning->purchase_price : 0,
                'valore_societario' => $mioDatoFin['valore'] ?? 130,
                'trend' => $mioDatoFin['trend'] ?? ['dir' => 'stable', 'perc' => 0],
                'probabilita_vittoria' => round((($mioDatoFin['valore'] ?? 130) / 570) * 100, 1)
            ];
        }

        return Inertia::render('Dashboard', [
            'leagues' => $leagues, 
            'myData' => $myData, 
            'stats' => $stats, 
            'classifica' => $classifica,
            'allTeams' => $allTeamsValues
        ]);
    }

    // --- FUNZIONI CREAZIONE E UNIONE LEGA (Per nuovi account) ---
    public function create() { 
        return Inertia::render('Leagues/Create'); 
    }

    public function store(Request $request) {
        $l = League::create([
            'name' => $request->name, 
            'invite_code' => strtoupper(Str::random(8)), 
            'admin_id' => auth()->id(), 
            'initial_budget' => $request->initial_budget ?? 500
        ]);
        LeagueParticipant::create([
            'league_id' => $l->id, 
            'user_id' => auth()->id(), 
            'team_name' => $request->team_name, 
            'remaining_budget' => $l->initial_budget, 
            'years_budget' => 40
        ]);
        return redirect()->route('dashboard');
    }

    public function join() { 
        return Inertia::render('Leagues/Join'); 
    }

    public function joinStore(Request $request) {
        $l = League::where('invite_code', $request->invite_code)->first();
        if (!$l) return back()->withErrors(['invite_code' => 'Codice non valido']);
        LeagueParticipant::create([
            'league_id' => $l->id, 
            'user_id' => auth()->id(), 
            'team_name' => $request->team_name, 
            'remaining_budget' => $l->initial_budget, 
            'years_budget' => 40
        ]);
        return redirect()->route('dashboard');
    }

    // --- AGGIORNAMENTO CAMPIONATO E STORICO ---
    public function updateCampionato(Request $request) {
        $investimentoBase = 130;
        $plusvalorePotenziale = 440;

        $topP = RealPlayer::where('role', 'P')->orderBy('quotation', 'desc')->limit(3)->get()->sum('quotation');
        $topD = RealPlayer::where('role', 'D')->orderBy('quotation', 'desc')->limit(8)->get()->sum('quotation');
        $topC = RealPlayer::where('role', 'C')->orderBy('quotation', 'desc')->limit(8)->get()->sum('quotation');
        $topA = RealPlayer::where('role', 'A')->orderBy('quotation', 'desc')->limit(6)->get()->sum('quotation');
        $benchmarkVal = ($topP + $topD + $topC + $topA) ?: 1;

        $calcolaGol = function($p) {
            if ($p < 66) return 0;
            if ($p < 70) return 1;
            return 2 + floor(($p - 70) / 5);
        };

        // 1. Salva dati squadre
        foreach ($request->classifica as $data) {
            $lp = LeagueParticipant::find($data['id']);
            if ($lp) {
                $lp->update([
                    'league_points' => $data['league_points'],
                    'total_points' => $data['total_points'],
                    'games_played' => $data['games_played'],
                ]);
            }
        }

        // 2. Ricalcola storia
        $firstTeam = LeagueParticipant::find($request->classifica[0]['id']);
        $tutti = LeagueParticipant::where('league_id', $firstTeam->league_id)->get();
        $maxGolLega = $tutti->map(fn($s) => $calcolaGol($s->games_played > 0 ? ($s->total_points / $s->games_played) : 0))->max() ?: 1;

        foreach ($tutti as $s) {
            $rSum = Roster::where('user_id', $s->user_id)->where('league_id', $s->league_id)->with('player')->get()->sum(fn($r) => $r->player->quotation ?? 0);
            $aQ = $rSum / $benchmarkVal;
            $mP = $s->games_played > 0 ? ($s->total_points / $s->games_played) : 0;
            $wE = $calcolaGol($mP) / $maxGolLega;
            $valoreAttuale = round($investimentoBase + ($plusvalorePotenziale * $aQ * $wE), 2);

            if ($s->games_played > 0) {
                MarketValueHistory::updateOrCreate(
                    ['user_id' => $s->user_id, 'league_id' => $s->league_id, 'matchday' => $s->games_played],
                    ['value' => $valoreAttuale, 'recorded_at' => now()]
                );
                MarketValueHistory::where('user_id', $s->user_id)->where('league_id', $s->league_id)->where('matchday', '>', $s->games_played)->delete();
            }
        }
        return redirect()->back()->with('message', 'Dati e Grafici aggiornati!');
    }

    // --- ALTRI METODI GESTIONE ---
    public function updateResources(Request $request) { 
        $p = LeagueParticipant::findOrFail($request->participant_id); 
        $p->update(['remaining_budget' => $request->new_credits, 'years_budget' => $request->new_years, 'remaining_primavera_budget' => $request->new_primavera_credits]); 
        return back()->with('message', 'Budget aggiornato!'); 
    }

    public function editCampionato() { 
        $l = auth()->user()->leagues()->first(); 
        $p = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); 
        return Inertia::render('Admin/CampionatoEdit', ['participants' => $p]); 
    }

    public function manageFinances() { $l = auth()->user()->leagues()->first(); $p = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); return Inertia::render('Admin/Finances', ['league' => $l, 'participants' => $p, 'stats' => ['totalCredits' => $p->sum('remaining_budget'), 'totalYears' => $p->sum('years_budget'), 'avgCredits' => round($p->avg('remaining_budget'))]]); }
    public function managePrimavera() { $l = auth()->user()->leagues()->first(); $teams = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); foreach ($teams as $t) { $t->primavera_players = PrimaveraRoster::where('league_id', $l->id)->where('user_id', $t->user_id)->with('player')->get(); } $sold = array_merge(Roster::where('league_id', $l->id)->pluck('real_player_id')->toArray(), PrimaveraRoster::where('league_id', $l->id)->pluck('real_player_id')->toArray()); $available = RealPlayer::whereNotIn('id', $sold)->orderBy('role', 'desc')->get(); return Inertia::render('Admin/Primavera', ['league' => $l, 'teams' => $teams, 'availablePlayers' => $available]); }
    public function manageCredits() { $l = auth()->user()->leagues()->first(); $teams = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); return Inertia::render('Admin/Credits', ['league' => $l, 'teams' => $teams]); }
    public function manageRosters() { $l = auth()->user()->leagues()->first(); $teams = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); foreach ($teams as $team) { $team->players = Roster::where('league_id', $l->id)->where('user_id', $team->user_id)->with('player')->get(); } $sold = array_merge(Roster::where('league_id', $l->id)->pluck('real_player_id')->toArray(), PrimaveraRoster::where('league_id', $l->id)->pluck('real_player_id')->toArray()); $avail = RealPlayer::whereNotIn('id', $sold)->orderBy('role', 'desc')->get(); return Inertia::render('Admin/Rosters', ['league' => $l, 'teams' => $teams, 'availablePlayers' => $avail]); }

    // --- AZIONI ROSE ---
    public function assignPlayer(Request $request) { $l = auth()->user()->leagues()->first(); Roster::create(['league_id' => $l->id, 'user_id' => $request->user_id, 'real_player_id' => $request->player_id, 'purchase_price' => $request->price, 'contract_years' => $request->years]); $p = LeagueParticipant::where('league_id', $l->id)->where('user_id', $request->user_id)->first(); $p->decrement('remaining_budget', $request->price); return back(); }
    public function assignManualPlayer(Request $request) { $l = auth()->user()->leagues()->first(); $np = RealPlayer::create(['name' => $request->name, 'role' => $request->role, 'real_team' => $request->real_team, 'initial_value' => 1, 'nationality' => $request->nationality ?? 'Italia']); Roster::create(['league_id' => $l->id, 'user_id' => $request->user_id, 'real_player_id' => $np->id, 'purchase_price' => $request->price, 'contract_years' => $request->years]); $p = LeagueParticipant::where('user_id', $request->user_id)->first(); if($p) $p->decrement('remaining_budget', $request->price); return back(); }
    public function removePlayer(Request $request) { $r = Roster::findOrFail($request->roster_id); $p = LeagueParticipant::where('user_id', $r->user_id)->first(); $p->increment('remaining_budget', $r->purchase_price); $r->delete(); return back(); }
    public function assignPrimavera(Request $request) { PrimaveraRoster::create(['league_id' => auth()->user()->leagues()->first()->id, 'user_id' => $request->user_id, 'real_player_id' => $request->player_id, 'purchase_price' => $request->price]); $p = LeagueParticipant::where('user_id', $request->user_id)->first(); $p->decrement('remaining_budget', $request->price); return back(); }
    public function removePrimavera(Request $request) { $item = PrimaveraRoster::findOrFail($request->roster_id); $p = LeagueParticipant::where('user_id', $item->user_id)->first(); $p->increment('remaining_budget', $item->purchase_price); $item->delete(); return back(); }
    
    // --- UTILITY ---
    public function societaIndex() { $l = auth()->user()->leagues()->first(); $parts = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); foreach ($parts as $p) { $p->years_used = Roster::where('league_id', $l->id)->where('user_id', $p->user_id)->sum('contract_years'); } return Inertia::render('Societa/Index', ['league' => $l, 'participants' => $parts]); }
    public function rankingIndex() { $rankingData = [['name' => 'SANTOS', 'points' => 49], ['name' => 'BOTAFOGO', 'points' => 44], ['name' => 'PALMEIRAS', 'points' => 43], ['name' => 'ATLETICO G MINEIRO', 'points' => 36], ['name' => 'VASCO DE GAMA', 'points' => 30], ['name' => 'CORINTHIANS', 'points' => 30], ['name' => 'FLAMENGO', 'points' => 26], ['name' => 'CRUZEIRO E.C.', 'points' => 18], ['name' => 'FLUMINENSE', 'points' => 0], ['name' => 'SAO PAULO', 'points' => 0]]; return Inertia::render('Lega/Ranking', ['ranking' => $rankingData, 'lastUpdate' => now()->format('d/m/Y')]); }
}