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

    // SALVA NUOVA SESSIONE
    // SALVA NUOVA SESSIONE (Versione corretta per il timer)
    public function storeSession(Request $request) 
    {
        $p = LeagueParticipant::where('user_id', auth()->id())->first();
        
        $request->validate([
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'auction_time' => 'required', // Può essere "10" o "00:10" o "1:30"
            'roles' => 'required|array'
        ]);

        $inputTime = $request->auction_time;
        $minutes = 0;

        // Se l'utente usa il formato HH:MM (es. 01:30 o 00:10)
        if (str_contains($inputTime, ':')) {
            $parts = explode(':', $inputTime);
            $minutes = ((int)$parts[0] * 60) + (int)$parts[1];
        } else {
            // Se l'utente scrive solo un numero (es. 10)
            $minutes = (int)$inputTime;
        }

        // Protezione: se il calcolo fallisce o mettono 0, mettiamo 5 minuti di default
        if ($minutes <= 0) $minutes = 5;

        MarketSession::create([
            'league_id' => $p->league_id,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'auction_duration' => $minutes, // Salviamo i minuti reali scelti
            'allowed_roles' => implode(',', $request->roles)
        ]);

        return back()->with('message', 'Sessione salvata! Durata aste: ' . $minutes . ' minuti.');
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

    // --- ALTRE FUNZIONI (Rosa, Aste) ---
    public function myRosterPage() { $u = auth()->user(); $p = LeagueParticipant::where('user_id', $u->id)->first(); if (!$p) return redirect()->route('dashboard'); $pros = Roster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get()->map(function($i){$i->is_primavera=false; return $i;}); $juniors = PrimaveraRoster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get()->map(function($i){$i->is_primavera=true; return $i;}); $merged = $pros->concat($juniors)->sortBy([function($a,$b){$o=['P'=>1,'D'=>2,'C'=>3,'A'=>4]; return $o[$a->player->role]<=>$o[$b->player->role];},['player.name','asc']])->values()->all(); $val = $pros->sum('purchase_price') + $juniors->sum('purchase_price'); return Inertia::render('Roster/Index', ['myData' => $p, 'myPlayers' => $merged, 'rosterValue' => (int)$val]); }
    
    public function auctions() 
    {
        $u = auth()->user();
        $p = LeagueParticipant::where('user_id', $u->id)->first();
        if (!$p) return redirect()->route('dashboard');
        
        $l = League::find($p->league_id);
        
        // Sincronizziamo anche qui
        $now = Carbon::now('Europe/Rome');
        
        $curr = MarketSession::where('league_id', $l->id)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->first();
            
            return Inertia::render('Market/Auctions', ['league' => $l, 'isMarketOpen' => (bool)$curr, 'currentSession' => $curr, 'myData' => $p, 'frozenCredits' => (int)(Auction::where('league_id', $l->id)->where('user_id', $u->id)->where('is_finished', false)->sum('current_bid') ?? 0), 'myRoster' => Roster::where('league_id', $l->id)->where('user_id', $u->id)->with('player')->get(), 'availablePlayers' => RealPlayer::whereNotIn('id', array_merge(Roster::where('league_id', $l->id)->pluck('real_player_id')->toArray(), PrimaveraRoster::where('league_id', $l->id)->pluck('real_player_id')->toArray()))->orderBy('role', 'desc')->get(), 'activeAuctions' => Auction::where('league_id', $l->id)->where('is_finished', false)->with(['player', 'user'])->get()]); }
    
    public function buy(Request $request) 
    {
        $l = League::findOrFail($request->league_id);
        // USIAMO SEMPRE QUESTO FORMATO
        $now = Carbon::now('Europe/Rome'); 
        
        $s = MarketSession::where('league_id', $l->id)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->first();
            
        if (!$s) {
            // Se entri qui, significa che per il server il mercato è chiuso
            return back()->withErrors(['error' => 'Il mercato è chiuso. Orario server: ' . $now->format('H:i')]);
        }

    private function executeBiddingWar($a, $lId, $lA) { $bo = Autobid::where('auction_id', $a->id)->where('user_id', '!=', $lId)->orderBy('max_bid', 'desc')->orderBy('created_at', 'asc')->first(); if ($bo) { if ($bo->max_bid > $lA) $a->update(['user_id' => $bo->user_id, 'current_bid' => $lA + 1]); elseif ($bo && $bo->max_bid == $lA) $a->update(['user_id' => $bo->user_id, 'current_bid' => $lA]); else $a->update(['user_id' => $lId, 'current_bid' => $lA]); } else { if ($lA > $a->current_bid) $a->update(['user_id' => $lId, 'current_bid' => $lA]); } }
    
    public function release(Request $request) { $r = Roster::with('player')->findOrFail($request->roster_id); $p = LeagueParticipant::where('user_id', $r->user_id)->first(); if ($p) { $p->increment('remaining_budget', ceil(($r->release_clause ?: $r->purchase_price) / 2)); $p->decrement('years_budget', ($r->contract_years - floor($r->contract_years / 2))); } $r->delete(); return back(); }
    
    public function updateContract(Request $request) { $r = Roster::findOrFail($request->roster_id); $r->update(['contract_years' => $request->new_years, 'release_clause' => ($r->release_clause ?: $r->purchase_price) + $request->clausola_investment]); return back(); }
    
    public function history() { $p = LeagueParticipant::where('user_id', auth()->id())->first(); return Inertia::render('Market/History', ['league' => League::find($p->league_id), 'movements' => Roster::where('league_id', $p->league_id)->with(['player', 'user'])->orderBy('created_at', 'desc')->get()]); }

    // --- FINANZE AGGIORNATE ---
    public function financesPage()
    {
        $user = auth()->user();
        $leagues = $user->leagues()->get();
        $firstLeague = $leagues->first();
        $lp = LeagueParticipant::where('user_id', $user->id)->where('league_id', $firstLeague->id)->first();

        if (!$lp || $lp->games_played == 0) {
            return Inertia::render('Societa/Finanze', ['stats' => null, 'message' => 'Gioca almeno una partita per attivare il Financial Terminal.']);
        }

        // 1. Parametri fissi
        $investimentoBase = 130;
        $plusvaloreMax = 440;

        // 2. Calcolo Benchmark Top 25 per Ruolo
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

        // 3. Calcolo Valori e Trend per TUTTE le squadre
        $allTeamsValues = [];
        foreach ($tutti as $s) {
            $rSum = Roster::where('user_id', $s->user_id)->where('league_id', $s->league_id)->with('player')->get()->sum(fn($r) => $r->player->quotation ?? 0);
            $assetQual = $rSum / $benchmarkVal;
            $mediaP = $s->games_played > 0 ? ($s->total_points / $s->games_played) : 0;
            $winEff = $calcolaGol($mediaP) / $maxGolLega;
            $valoreAttuale = $investimentoBase + ($plusvaloreMax * $assetQual * $winEff);

            $prev = MarketValueHistory::where('user_id', $s->user_id)->where('matchday', '<', $s->games_played)->orderBy('matchday', 'desc')->first();
            $trend = ['dir' => 'stable', 'perc' => 0];
            if ($prev && $prev->value > 0) {
                $diff = $valoreAttuale - $prev->value;
                $trend = ['dir' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'stable'), 'perc' => round(abs(($diff / $prev->value) * 100), 1)];
            }
            $allTeamsValues[] = ['user_id' => $s->user_id, 'team_name' => $s->team_name, 'valore' => round($valoreAttuale, 2), 'trend' => $trend];
        }
        usort($allTeamsValues, fn($a, $b) => $b['valore'] <=> $a['valore']);

        // 4. Dati Utente Loggato
        $mioDato = collect($allTeamsValues)->firstWhere('user_id', $user->id);
        $tuaMediaPunti = $lp->total_points / $lp->games_played;

        $stats = [
            'valore_monetario' => $mioDato['valore'],
            'trend' => $mioDato['trend'],
            'asset_quality_perc' => round(($rosterSum ?? 0 / $benchmarkVal) * 100, 1), // Calcolato dinamicamente per l'utente loggato nel ciclo sopra
            'winning_efficiency_perc' => round(($calcolaGol($tuaMediaPunti) / $maxGolLega) * 100, 1),
            'tuoi_gol' => $calcolaGol($tuaMediaPunti),
            'leader_gol' => $maxGolLega,
            'benchmark_val' => $benchmarkVal,
            'media_punti' => round($tuaMediaPunti, 2)
        ];

        // Nota: Il calcolo asset_quality_perc sopra usa $rosterSum del ciclo. 
        // Per sicurezza ricalcoliamolo pulito per l'utente loggato qui:
        $myRosterSum = Roster::where('user_id', $user->id)->where('league_id', $firstLeague->id)->with('player')->get()->sum(fn($r) => $r->player->quotation ?? 0);
        $stats['asset_quality_perc'] = round(($myRosterSum / $benchmarkVal) * 100, 1);

        return Inertia::render('Societa/Finanze', [
            'leagues' => $leagues,
            'myData' => $lp,
            'stats' => $stats,
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