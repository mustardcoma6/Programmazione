<?php

namespace App\Http\Controllers;

// Qui ho aggiunto TUTTI i modelli necessari per non avere più errori "Not Found"
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

        if ($firstLeague) {
            $myData = LeagueParticipant::where('league_id', $firstLeague->id)->where('user_id', $user->id)->first();
            
            $classifica = LeagueParticipant::where('league_id', $firstLeague->id)
                ->with('user')
                ->orderBy('league_points', 'desc')
                ->orderBy('total_points', 'desc')
                ->get();

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

            $rosterSum = Roster::where('user_id', $user->id)->where('league_id', $firstLeague->id)
                ->with('player')->get()->sum(fn($r) => $r->player->quotation ?? 0);
            $assetQual = $rosterSum / $benchmarkVal;
            $mediaP = $myData->games_played > 0 ? ($myData->total_points / $myData->games_played) : 0;
            $winEff = $calcolaGol($mediaP) / $maxGolLega;

            $valoreAttuale = 130 + (440 * $assetQual * $winEff);
            $probVittoria = ($assetQual * $winEff) * 100;

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
                'probabilita_vittoria' => round($probVittoria, 1)
            ];
        }

        return Inertia::render('Dashboard', [
            'leagues' => $leagues, 
            'myData' => $myData, 
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
        $investimentoBase = 130;
        $plusvalorePotenziale = 440;

        $topP = RealPlayer::where('role', 'P')->orderBy('quotation', 'desc')->limit(3)->get()->sum('quotation');
        $topD = RealPlayer::where('role', 'D')->orderBy('quotation', 'desc')->limit(8)->get()->sum('quotation');
        $topC = RealPlayer::where('role', 'C')->orderBy('quotation', 'desc')->limit(8)->get()->sum('quotation');
        $topA = RealPlayer::where('role', 'A')->orderBy('quotation', 'desc')->limit(6)->get()->sum('quotation');
        $benchmarkQuotazione = ($topP + $topD + $topC + $topA) ?: 1;

        $calcolaGol = function($p) {
            if ($p < 66) return 0;
            if ($p < 70) return 1;
            return 2 + floor(($p - 70) / 5);
        };

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

        $firstTeam = LeagueParticipant::find($request->classifica[0]['id']);
        $tutti = LeagueParticipant::where('league_id', $firstTeam->league_id)->get();
        $maxGolLega = $tutti->map(fn($s) => $calcolaGol($s->games_played > 0 ? ($s->total_points / $s->games_played) : 0))->max() ?: 1;

        foreach ($tutti as $s) {
            $rosterSum = Roster::where('user_id', $s->user_id)->where('league_id', $s->league_id)->with('player')->get()->sum(fn($r) => $r->player->quotation ?? 0);
            $assetQual = $rosterSum / $benchmarkQuotazione;
            $mediaP = $s->games_played > 0 ? ($s->total_points / $s->games_played) : 0;
            $winEff = $calcolaGol($mediaP) / $maxGolLega;
            $valoreAttuale = round($investimentoBase + ($plusvalorePotenziale * $assetQual * $winEff), 2);

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

    public function manageFinances() { $l = auth()->user()->leagues()->first(); $p = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); return Inertia::render('Admin/Finances', ['league' => $l, 'participants' => $p, 'stats' => ['totalCredits' => $p->sum('remaining_budget'), 'totalYears' => $p->sum('years_budget'), 'avgCredits' => round($p->avg('remaining_budget'))]]); }
    
    public function managePrimavera() { 
        $l = auth()->user()->leagues()->first(); 
        $teams = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); 
        foreach ($teams as $t) { 
            $t->primavera_players = PrimaveraRoster::where('league_id', $l->id)->where('user_id', $t->user_id)->with('player')->get(); 
        } 
        $sold = array_merge(Roster::where('league_id', $l->id)->pluck('real_player_id')->toArray(), PrimaveraRoster::where('league_id', $l->id)->pluck('real_player_id')->toArray()); 
        $available = RealPlayer::whereNotIn('id', $sold)->orderBy('role', 'desc')->get(); 
        return Inertia::render('Admin/Primavera', ['league' => $l, 'teams' => $teams, 'availablePlayers' => $available]); 
    }

    public function manageCredits() { $l = auth()->user()->leagues()->first(); $teams = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); return Inertia::render('Admin/Credits', ['league' => $l, 'teams' => $teams]); }

    public function manageRosters() { 
        $l = auth()->user()->leagues()->first(); 
        $teams = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); 
        foreach ($teams as $team) { 
            $team->players = Roster::where('league_id', $l->id)->where('user_id', $team->user_id)->with('player')->get(); 
        } 
        $sold = array_merge(Roster::where('league_id', $l->id)->pluck('real_player_id')->toArray(), PrimaveraRoster::where('league_id', $l->id)->pluck('real_player_id')->toArray()); 
        $avail = RealPlayer::whereNotIn('id', $sold)->orderBy('role', 'desc')->get(); 
        return Inertia::render('Admin/Rosters', ['league' => $l, 'teams' => $teams, 'availablePlayers' => $avail]); 
    }

    public function removePlayer(Request $request) { $r = Roster::findOrFail($request->roster_id); $p = LeagueParticipant::where('user_id', $r->user_id)->first(); $p->increment('remaining_budget', $r->purchase_price); $r->delete(); return back(); }
    public function societaIndex() { $l = auth()->user()->leagues()->first(); $parts = LeagueParticipant::where('league_id', $l->id)->with('user')->get(); foreach ($parts as $p) { $p->years_used = Roster::where('league_id', $l->id)->where('user_id', $p->user_id)->sum('contract_years'); } return Inertia::render('Societa/Index', ['league' => $l, 'participants' => $parts]); }
    public function rankingIndex()
    {
        $rankingData = [['name' => 'SANTOS', 'points' => 49], ['name' => 'BOTAFOGO', 'points' => 44], ['name' => 'PALMEIRAS', 'points' => 43], ['name' => 'ATLETICO G MINEIRO', 'points' => 36], ['name' => 'VASCO DE GAMA', 'points' => 30], ['name' => 'CORINTHIANS', 'points' => 30], ['name' => 'FLAMENGO', 'points' => 26], ['name' => 'CRUZEIRO E.C.', 'points' => 18], ['name' => 'FLUMINENSE', 'points' => 0], ['name' => 'SAO PAULO', 'points' => 0]];
        return Inertia::render('Lega/Ranking', ['ranking' => $rankingData, 'lastUpdate' => now()->format('d/m/Y')]);
    }
}