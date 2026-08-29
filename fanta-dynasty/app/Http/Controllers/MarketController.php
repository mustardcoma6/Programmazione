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
    public function storeSession(Request $request) 
    {
        $p = LeagueParticipant::where('user_id', auth()->id())->first();
        
        $request->validate([
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'auction_time' => 'required',
            'roles' => 'required|array'
        ]);

        $t = explode(':', $request->auction_time); 
        $tm = (isset($t[1])) ? ($t[0] * 60) + $t[1] : 90;

        MarketSession::create([
            'league_id' => $p->league_id,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'auction_duration' => $tm,
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

    // --- ALTRE FUNZIONI (Rosa, Aste, Finanze...) ---
    public function myRosterPage() { $u = auth()->user(); $p = LeagueParticipant::where('user_id', $u->id)->first(); if (!$p) return redirect()->route('dashboard'); $pros = Roster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get()->map(function($i){$i->is_primavera=false; return $i;}); $juniors = PrimaveraRoster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get()->map(function($i){$i->is_primavera=true; return $i;}); $merged = $pros->concat($juniors)->sortBy([function($a,$b){$o=['P'=>1,'D'=>2,'C'=>3,'A'=>4]; return $o[$a->player->role]<=>$o[$b->player->role];},['player.name','asc']])->values()->all(); $val = $pros->sum('purchase_price') + $juniors->sum('purchase_price'); return Inertia::render('Roster/Index', ['myData' => $p, 'myPlayers' => $merged, 'rosterValue' => (int)$val]); }
    public function auctions() { $u = auth()->user(); $p = LeagueParticipant::where('user_id', $u->id)->first(); if (!$p) return redirect()->route('dashboard'); $l = League::find($p->league_id); $this->processExpiredAuctions($l->id); $now = now(); $curr = MarketSession::where('league_id', $l->id)->where('start_at', '<=', $now)->where('end_at', '>=', $now)->first(); return Inertia::render('Market/Auctions', ['league' => $l, 'isMarketOpen' => (bool)$curr, 'currentSession' => $curr, 'myData' => $p, 'frozenCredits' => (int)(Auction::where('league_id', $l->id)->where('user_id', $u->id)->where('is_finished', false)->sum('current_bid') ?? 0), 'myRoster' => Roster::where('league_id', $l->id)->where('user_id', $u->id)->with('player')->get(), 'availablePlayers' => RealPlayer::whereNotIn('id', array_merge(Roster::where('league_id', $l->id)->pluck('real_player_id')->toArray(), PrimaveraRoster::where('league_id', $l->id)->pluck('real_player_id')->toArray()))->orderBy('role', 'desc')->get(), 'activeAuctions' => Auction::where('league_id', $l->id)->where('is_finished', false)->with(['player', 'user'])->get()]); }
    public function buy(Request $request) { $l = League::findOrFail($request->league_id); $now = Carbon::now('Europe/Rome'); $s = MarketSession::where('league_id', $l->id)->where('start_at', '<=', $now)->where('end_at', '>=', $now)->first(); if (!$s) return back(); $a = Auction::where('league_id', $l->id)->where('real_player_id', $request->player_id)->where('is_finished', false)->first(); if (!$a) { $a = Auction::create(['league_id' => $l->id, 'real_player_id' => $request->player_id, 'user_id' => auth()->id(), 'current_bid' => 0, 'expires_at' => $now->copy()->addMinutes($s->auction_duration), 'is_finished' => false]); } $nb = $request->price ?? ($a->current_bid + 1); $this->executeBiddingWar($a, auth()->id(), $nb); if ($now->diffInSeconds($a->expires_at, false) <= 30) $a->update(['expires_at' => $now->copy()->addMinute()]); return back(); }
    private function executeBiddingWar($a, $lId, $lA) { $bo = Autobid::where('auction_id', $a->id)->where('user_id', '!=', $lId)->orderBy('max_bid', 'desc')->orderBy('created_at', 'asc')->first(); if ($bo) { if ($bo->max_bid > $lA) $a->update(['user_id' => $bo->user_id, 'current_bid' => $lA + 1]); elseif ($bo && $bo->max_bid == $lA) $a->update(['user_id' => $bo->user_id, 'current_bid' => $lA]); else $a->update(['user_id' => $lId, 'current_bid' => $lA]); } else { if ($lA > $a->current_bid) $a->update(['user_id' => $lId, 'current_bid' => $lA]); } }
    public function release(Request $request) { $r = Roster::with('player')->findOrFail($request->roster_id); $p = LeagueParticipant::where('user_id', $r->user_id)->first(); if ($p) { $p->increment('remaining_budget', ceil(($r->release_clause ?: $r->purchase_price) / 2)); $p->decrement('years_budget', ($r->contract_years - floor($r->contract_years / 2))); } $r->delete(); return back(); }
    public function updateContract(Request $request) { $r = Roster::findOrFail($request->roster_id); $r->update(['contract_years' => $request->new_years, 'release_clause' => ($r->release_clause ?: $r->purchase_price) + $request->clausola_investment]); return back(); }
    public function history() { $p = LeagueParticipant::where('user_id', auth()->id())->first(); return Inertia::render('Market/History', ['league' => League::find($p->league_id), 'movements' => Roster::where('league_id', $p->league_id)->with(['player', 'user'])->orderBy('created_at', 'desc')->get()]); }
   public function financesPage()
{
    $user = auth()->user();
    $lp = \App\Models\LeagueParticipant::where('user_id', $user->id)->first();
    $investimentoIniziale = 130;
    $premioMassimo = 570;
    $plusvalorePotenziale = 440; // 570 - 130

    if (!$lp || $lp->games_played == 0) {
        return Inertia::render('Societa/Finanze', ['stats' => null, 'message' => 'Gioca almeno una partita per calcolare i valori.']);
    }

    // --- 1. BENCHMARK TOP 25 PER RUOLO (Il 100% del valore Rosa) ---
    $topP = \App\Models\RealPlayer::where('role', 'P')->orderBy('quotation', 'desc')->limit(3)->get()->sum('quotation');
    $topD = \App\Models\RealPlayer::where('role', 'D')->orderBy('quotation', 'desc')->limit(8)->get()->sum('quotation');
    $topC = \App\Models\RealPlayer::where('role', 'C')->orderBy('quotation', 'desc')->limit(8)->get()->sum('quotation');
    $topA = \App\Models\RealPlayer::where('role', 'A')->orderBy('quotation', 'desc')->limit(6)->get()->sum('quotation');
    $benchmarkQuotazione = $topP + $topD + $topC + $topA;

    // --- 2. ASSET QUALITY (Tua Rosa vs Top 25) ---
    $roster = \App\Models\Roster::where('user_id', $user->id)->with('player')->get();
    $tuaRosaSum = $roster->sum(fn($r) => $r->player->quotation ?? 0);
    $assetQuality = $tuaRosaSum / ($benchmarkQuotazione ?: 1);

    // --- 3. WINNING EFFICIENCY (Classifica delle Medie Gol) ---
    $calcolaGol = function($p) {
        if ($p < 66) return 0;
        if ($p < 70) return 1;
        return 2 + floor(($p - 70) / 5);
    };

    // Calcoliamo i gol medi di TUTTE le squadre per trovare il Leader
    $tutti = \App\Models\LeagueParticipant::where('league_id', $lp->league_id)->get();
    $golSquadre = $tutti->map(function($squadra) use ($calcolaGol) {
        $mediaP = $squadra->games_played > 0 ? ($squadra->total_points / $squadra->games_played) : 0;
        return [
            'user_id' => $squadra->user_id,
            'gol' => $calcolaGol($mediaP)
        ];
    });

    $tuoiGol = $golSquadre->firstWhere('user_id', $user->id)['gol'];
    $maxGolLega = $golSquadre->max('gol') ?: 1; // Chi è il migliore della lega?

    // Efficienza = Rapporto rispetto al migliore (Il migliore è il 100%)
    $winningEfficiency = $tuoiGol / $maxGolLega;

    // --- 4. VALORE DI VENDITA FINALE (L'Algoritmo di Borsa) ---
    // Prezzo = Base + (Potenziale * Qualità Rosa * Efficienza Risultati)
    $valoreFinale = $investimentoIniziale + ($plusvalorePotenziale * $assetQuality * $winningEfficiency);

    return Inertia::render('Societa/Finanze', [
        'leagues' => $user->leagues()->get(),
        'myData' => $lp,
        'stats' => [
            'valore_monetario' => round($valoreFinale, 2),
            'asset_quality_perc' => round($assetQuality * 100, 1),
            'winning_efficiency_perc' => round($winningEfficiency * 100, 1),
            'tua_rosa_val' => $tuaRosaSum,
            'benchmark_val' => $benchmarkQuotazione,
            'tuoi_gol' => $tuoiGol,
            'leader_gol' => $maxGolLega,
            'media_punti' => round($lp->total_points / $lp->games_played, 2)
        ]
    ]);
}
    public function primaveraPage() { $u = auth()->user(); $p = LeagueParticipant::where('user_id', $u->id)->first(); $players = PrimaveraRoster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get(); return Inertia::render('Societa/Primavera', ['myData' => $p, 'primaveraPlayers' => $players]); }
    private function processExpiredAuctions($leagueId) {
        $now = Carbon::now('Europe/Rome');
        
        DB::transaction(function () use ($leagueId, $now) {
            // Prendiamo le aste scadute
            $expired = Auction::where('league_id', $leagueId)
                ->where('is_finished', false)
                ->where('expires_at', '<=', $now)
                ->lockForUpdate()
                ->get();

            foreach ($expired as $auc) {
                // 1. Assegnazione al Roster (con 1 anno di contratto base)
                Roster::create([
                    'league_id' => $auc->league_id, 
                    'user_id' => $auc->user_id, 
                    'real_player_id' => $auc->real_player_id, 
                    'purchase_price' => $auc->current_bid, 
                    'contract_years' => 1
                ]);

                // 2. Troviamo il partecipante per scalare i budget
                $p = LeagueParticipant::where('league_id', $auc->league_id)
                    ->where('user_id', $auc->user_id)
                    ->first();

                if ($p) {
                    // SCALANO I CREDITI
                    $p->decrement('remaining_budget', $auc->current_bid);
                    
                    // SCALA AUTOMATICAMENTE 1 ANNO DAL BUDGET TOTALE (Punto richiesto)
                    $p->decrement('years_budget', 1);
                }

                // 3. Chiudiamo l'asta
                $auc->update(['is_finished' => true]);
            }
        });
    }
}