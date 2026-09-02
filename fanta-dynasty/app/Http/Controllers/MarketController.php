<?php

namespace App\Http\Controllers;

use App\Models\{RealPlayer, LeagueParticipant, Roster, PrimaveraRoster, League, Auction, MarketSession, Autobid, MarketValueHistory};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarketController extends Controller
{
    // MOSTRA IL CALENDARIO SESSIONI
    public function sessions() 
    {
        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();
        
        if (!$participant) return redirect()->route('dashboard');

        $league = League::find($participant->league_id);
        $sessions = MarketSession::where('league_id', $league->id)->orderBy('start_at', 'desc')->get();

        return Inertia::render('Market/Sessions', [
            'league' => $league,
            'sessions' => $sessions
        ]);
    }

    // SALVA NUOVA SESSIONE (Timer flessibile)
    public function storeSession(Request $request) 
    {
        $p = LeagueParticipant::where('user_id', auth()->id())->first();
        
        $request->validate([
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'auction_time' => 'required',
            'roles' => 'required|array'
        ]);

        $inputTime = $request->auction_time;
        $minutes = 0;

        if (str_contains($inputTime, ':')) {
            $parts = explode(':', $inputTime);
            $minutes = ((int)$parts[0] * 60) + (int)$parts[1];
        } else {
            $minutes = (int)$inputTime;
        }

        if ($minutes <= 0) $minutes = 5;

        MarketSession::create([
            'league_id' => $p->league_id,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'auction_duration' => $minutes,
            'allowed_roles' => implode(',', $request->roles)
        ]);

        return back()->with('message', 'Sessione salvata!');
    }

    // CHIUSURA EMERGENZA
    public function closeMarketNow() 
    {
        $p = LeagueParticipant::where('user_id', auth()->id())->first();
        if ($p) {
            MarketSession::where('league_id', $p->league_id)->where('end_at', '>', now())->update(['end_at' => now()]);
            Auction::where('league_id', $p->league_id)->where('is_finished', false)->delete();
        }
        return back()->with('message', 'Aste annullate.');
    }

    // VISUALIZZAZIONE ROSA
    public function myRosterPage() 
    { 
        $u = auth()->user(); 
        $p = LeagueParticipant::where('user_id', $u->id)->first(); 
        if (!$p) return redirect()->route('dashboard'); 
        
        $pros = Roster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get()->map(function($i){$i->is_primavera=false; return $i;}); 
        $juniors = PrimaveraRoster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get()->map(function($i){$i->is_primavera=true; return $i;}); 
        $merged = $pros->concat($juniors)->sortBy([function($a,$b){$o=['P'=>1,'D'=>2,'C'=>3,'A'=>4]; return $o[$a->player->role]<=>$o[$b->player->role];},['player.name','asc']])->values()->all(); 
        $val = $pros->sum('purchase_price') + $juniors->sum('purchase_price'); 
        
        return Inertia::render('Roster/Index', ['myData' => $p, 'myPlayers' => $merged, 'rosterValue' => (int)$val]); 
    }
    
    // VISUALIZZAZIONE ASTE (Fuso Orario Roma)
    public function auctions() 
    {
        $u = auth()->user();
        $p = LeagueParticipant::where('user_id', $u->id)->first();
        if (!$p) return redirect()->route('dashboard');
        
        $l = League::find($p->league_id);
        $this->processExpiredAuctions($l->id);
        
        // Sincronizziamo l'ora esatta di Roma
        $now = Carbon::now('Europe/Rome')->toDateTimeString();
        
        $curr = MarketSession::where('league_id', $l->id)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->first();

        // Passiamo i dati alla pagina
        return Inertia::render('Market/Auctions', [
            'league' => $l, 
            'isMarketOpen' => (bool)$curr, 
            'currentSession' => $curr, 
            // ... (restanti props)
        ]);
    }
    
    // AZIONE DI ACQUISTO (Fuso Orario Roma)
    public function buy(Request $request) 
    {
        $l = League::findOrFail($request->league_id);
        
        // Prendiamo l'ora di Roma e togliamo i millisecondi
        $now = Carbon::now('Europe/Rome')->toDateTimeString(); 
        
        $s = MarketSession::where('league_id', $l->id)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->first();
            
        if (!$s) {
            // Se fallisce, restituiamo un errore che ci dice CHE ORA È per il server
            return back()->withErrors([
                'error' => 'Mercato chiuso. Server: ' . Carbon::now('Europe/Rome')->format('H:i') . 
                           '. Inizio previsto: ' . Carbon::parse($s->start_at ?? now())->format('H:i')
            ]);
        }

        $a = Auction::where('league_id', $l->id)
            ->where('real_player_id', $request->player_id)
            ->where('is_finished', false)
            ->first();

        if (!$a) {
            $a = Auction::create([
                'league_id' => $l->id,
                'real_player_id' => $request->player_id,
                'user_id' => auth()->id(),
                'current_bid' => 0,
                'expires_at' => $now->copy()->addMinutes($s->auction_duration),
                'is_finished' => false
            ]);
        }

        $nb = $request->price ?? ($a->current_bid + 1);
        $this->executeBiddingWar($a, auth()->id(), $nb);

        if ($now->diffInSeconds($a->expires_at, false) <= 30) {
            $a->update(['expires_at' => $now->copy()->addMinute()]);
        }

        return back();
    } // <-- Questa chiusura mancava!

    private function executeBiddingWar($a, $lId, $lA) 
    { 
        $bo = Autobid::where('auction_id', $a->id)->where('user_id', '!=', $lId)->orderBy('max_bid', 'desc')->orderBy('created_at', 'asc')->first(); 
        if ($bo) { 
            if ($bo->max_bid > $lA) $a->update(['user_id' => $bo->user_id, 'current_bid' => $lA + 1]); 
            elseif ($bo && $bo->max_bid == $lA) $a->update(['user_id' => $bo->user_id, 'current_bid' => $lA]); 
            else $a->update(['user_id' => $lId, 'current_bid' => $lA]); 
        } else { 
            if ($lA > $a->current_bid) $a->update(['user_id' => $lId, 'current_bid' => $lA]); 
        } 
    }
    
    public function release(Request $request) 
    { 
        $r = Roster::with('player')->findOrFail($request->roster_id); 
        $p = LeagueParticipant::where('user_id', $r->user_id)->first(); 
        if ($p) { 
            $p->increment('remaining_budget', ceil(($r->release_clause ?: $r->purchase_price) / 2)); 
            $p->decrement('years_budget', ($r->contract_years - floor($r->contract_years / 2))); 
        } 
        $r->delete(); 
        return back(); 
    }
    
    public function updateContract(Request $request) 
    { 
        $r = Roster::findOrFail($request->roster_id); 
        $r->update(['contract_years' => $request->new_years, 'release_clause' => ($r->release_clause ?: $r->purchase_price) + $request->clausola_investment]); 
        return back(); 
    }
    
    public function history() 
    { 
        $p = LeagueParticipant::where('user_id', auth()->id())->first(); 
        return Inertia::render('Market/History', ['league' => League::find($p->league_id), 'movements' => Roster::where('league_id', $p->league_id)->with(['player', 'user'])->orderBy('created_at', 'desc')->get()]); 
    }

    // --- FINANZE ---
    public function financesPage()
    {
        $user = auth()->user();
        $leagues = $user->leagues()->get();
        $firstLeague = $leagues->first();
        $lp = LeagueParticipant::where('user_id', $user->id)->where('league_id', $firstLeague->id)->first();

        if (!$lp || $lp->games_played == 0) {
            return Inertia::render('Societa/Finanze', ['stats' => null, 'message' => 'Gioca almeno una partita.']);
        }

        $inv = 130; $plus = 440;
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

        $allTeamsValues = [];
        foreach ($tutti as $s) {
            $rSum = Roster::where('user_id', $s->user_id)->where('league_id', $s->league_id)->with('player')->get()->sum(fn($r) => $r->player->quotation ?? 0);
            $aQ = $rSum / $benchmarkVal;
            $mP = $s->games_played > 0 ? ($s->total_points / $s->games_played) : 0;
            $wE = $calcolaGol($mP) / $maxGolLega;
            $val = $inv + ($plus * $aQ * $wE);

            $prev = MarketValueHistory::where('user_id', $s->user_id)->where('matchday', '<', $s->games_played)->orderBy('matchday', 'desc')->first();
            $trend = ['dir' => 'stable', 'perc' => 0];
            if ($prev && $prev->value > 0) {
                $diff = $val - $prev->value;
                $trend = ['dir' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'stable'), 'perc' => round(abs(($diff / $prev->value) * 100), 1)];
            }
            $allTeamsValues[] = ['user_id' => $s->user_id, 'team_name' => $s->team_name, 'valore' => round($val, 2), 'trend' => $trend];
        }
        usort($allTeamsValues, fn($a, $b) => $b['valore'] <=> $a['valore']);

        $myFin = collect($allTeamsValues)->firstWhere('user_id', $user->id);
        $tuaMedia = $lp->total_points / $lp->games_played;

        return Inertia::render('Societa/Finanze', [
            'leagues' => $leagues,
            'myData' => $lp,
            'stats' => [
                'valore_monetario' => $myFin['valore'],
                'trend' => $myFin['trend'],
                'asset_quality_perc' => round((Roster::where('user_id', $user->id)->where('league_id', $firstLeague->id)->with('player')->get()->sum(fn($r)=>$r->player->quotation??0) / $benchmarkVal)*100, 1),
                'winning_efficiency_perc' => round(($calcolaGol($tuaMedia) / $maxGolLega) * 100, 1),
                'tuoi_gol' => $calcolaGol($tuaMedia),
                'leader_gol' => $maxGolLega,
                'benchmark_val' => $benchmarkVal
            ],
            'allTeams' => $allTeamsValues,
            'history' => MarketValueHistory::where('user_id', $user->id)->orderBy('matchday', 'asc')->get()
        ]);
    }

    public function primaveraPage() { $u = auth()->user(); $p = LeagueParticipant::where('user_id', $u->id)->first(); $players = PrimaveraRoster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get(); return Inertia::render('Societa/Primavera', ['myData' => $p, 'primaveraPlayers' => $players]); }

    private function processExpiredAuctions($leagueId) {
        $now = Carbon::now('Europe/Rome');
        DB::transaction(function () use ($leagueId, $now) {
            $expired = Auction::where('league_id', $leagueId)->where('is_finished', false)->where('expires_at', '<=', $now)->lockForUpdate()->get();
            foreach ($expired as $auc) {
                Roster::create(['league_id' => $auc->league_id, 'user_id' => $auc->user_id, 'real_player_id' => $auc->real_player_id, 'purchase_price' => $auc->current_bid, 'contract_years' => 1]);
                $p = LeagueParticipant::where('league_id', $auc->league_id)->where('user_id', $auc->user_id)->first();
                if ($p) { $p->decrement('remaining_budget', $auc->current_bid); $p->decrement('years_budget', 1); }
                $auc->update(['is_finished' => true]);
            }
        });
    }
}