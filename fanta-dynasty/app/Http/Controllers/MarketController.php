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
        $p = LeagueParticipant::where('user_id', $user->id)->first();
        if (!$p) return redirect()->route('dashboard');

        $league = League::find($p->league_id);
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

    // VISUALIZZAZIONE ASTE (Tutti i dati ripristinati)
    public function auctions() 
    {
        $u = auth()->user();
        $p = LeagueParticipant::where('user_id', $u->id)->first();
        if (!$p) return redirect()->route('dashboard');
        
        $l = League::find($p->league_id);
        $this->processExpiredAuctions($l->id);
        
        $now = Carbon::now('Europe/Rome');
        $curr = MarketSession::where('league_id', $l->id)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->first();

        // Recuperiamo i giocatori già occupati
        $takenPlayers = array_merge(
            Roster::where('league_id', $l->id)->pluck('real_player_id')->toArray(),
            PrimaveraRoster::where('league_id', $l->id)->pluck('real_player_id')->toArray()
        );

        return Inertia::render('Market/Auctions', [
            'league' => $l, 
            'isMarketOpen' => (bool)$curr, 
            'currentSession' => $curr, 
            'myData' => $p, 
            'frozenCredits' => (int)(Auction::where('league_id', $l->id)->where('user_id', $u->id)->where('is_finished', false)->sum('current_bid') ?? 0), 
            'myRoster' => Roster::where('league_id', $l->id)->where('user_id', $u->id)->with('player')->get(), 
            'availablePlayers' => RealPlayer::whereNotIn('id', $takenPlayers)->orderBy('role', 'desc')->get(), 
            'activeAuctions' => Auction::where('league_id', $l->id)->where('is_finished', false)->with(['player', 'user'])->get()
        ]);
    }
    
    // AZIONE DI ACQUISTO (Rilancio a sbalzo intelligente)
    public function buy(Request $request) 
    {
        $l = League::findOrFail($request->league_id);
        $now = Carbon::now('Europe/Rome'); 
        
        $s = MarketSession::where('league_id', $l->id)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->first();
            
        if (!$s) return back()->withErrors(['error' => 'Il mercato è chiuso.']);

        $a = Auction::where('league_id', $l->id)
            ->where('real_player_id', $request->player_id)
            ->where('is_finished', false)
            ->first();

        // Offerta massima inviata dall'utente
        $maxBidUtente = (int)$request->price;

        // CONTROLLO BUDGET: L'utente ha i crediti per coprire la sua offerta massima?
        $p = LeagueParticipant::where('league_id', $l->id)->where('user_id', auth()->id())->first();
        if ($p->remaining_budget < $maxBidUtente) {
            return back()->withErrors(['error' => 'Non hai abbastanza crediti per coprire questa offerta massima!']);
        }

        // Se l'asta non esiste, la creiamo
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

        // Se l'offerta massima inviata è più bassa del prezzo attuale, la rifiutiamo
        if ($maxBidUtente <= $a->current_bid) {
            return back()->withErrors(['error' => 'Devi impostare un limite più alto del prezzo attuale!']);
        }

        // Registriamo o aggiorniamo il limite massimo dell'utente
        Autobid::updateOrCreate(
            ['auction_id' => $a->id, 'user_id' => auth()->id()],
            ['max_bid' => $maxBidUtente]
        );

        // Calcoliamo la guerra di rilanci
        $this->executeBiddingWar($a);

        // Estensione tempo se mancano meno di 30 secondi
        if ($now->diffInSeconds($a->expires_at, false) <= 30) {
            $a->update(['expires_at' => $now->copy()->addMinute()]);
        }

        return back();
    }

    private function executeBiddingWar($a) 
    {
        // 1. Prendiamo i due Autobid più alti per questa asta
        $bids = Autobid::where('auction_id', $a->id)
            ->orderBy('max_bid', 'desc')
            ->orderBy('created_at', 'asc') // In caso di parità, vince chi ha puntato prima
            ->limit(2)
            ->get();

        if ($bids->count() === 0) return;

        $vincitore = $bids[0];
        $secondo = $bids[1] ?? null;

        if (!$secondo) {
            // Caso A: C'è solo un offerente. 
            // Il prezzo diventa 1 (se l'asta era a 0) o rimane quello attuale se già esistente.
            $a->update([
                'user_id' => $vincitore->user_id,
                'current_bid' => max(1, $a->current_bid)
            ]);
        } else {
            // Caso B: C'è una sfida.
            if ($vincitore->max_bid > $secondo->max_bid) {
                // Il vincitore vince superando di 1 il limite del secondo
                $a->update([
                    'user_id' => $vincitore->user_id,
                    'current_bid' => $secondo->max_bid + 1
                ]);
            } else {
                // Caso C: Parità perfetta. Vince il primo che ha creato l'autobid.
                // Il prezzo va al massimo di entrambi.
                $a->update([
                    'user_id' => $vincitore->user_id,
                    'current_bid' => $vincitore->max_bid
                ]);
            }
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
            $val = $inv + ($plus * ($rSum / $benchmarkVal) * ($calcolaGol($s->games_played > 0 ? ($s->total_points / $s->games_played) : 0) / $maxGolLega));

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
        $myRosterSum = Roster::where('user_id', $user->id)->where('league_id', $firstLeague->id)->with('player')->get()->sum(fn($r) => $r->player->quotation ?? 0);

        return Inertia::render('Societa/Finanze', [
            'leagues' => $leagues,
            'myData' => $lp,
            'stats' => [
                'valore_monetario' => $myFin['valore'],
                'trend' => $myFin['trend'],
                'asset_quality_perc' => round(($myRosterSum / $benchmarkVal) * 100, 1),
                'winning_efficiency_perc' => round(($calcolaGol($tuaMedia) / $maxGolLega) * 100, 1),
                'tuoi_gol' => $calcolaGol($tuaMedia),
                'leader_gol' => $maxGolLega,
                'benchmark_val' => $benchmarkVal
            ],
            'allTeams' => $allTeamsValues,
            'history' => MarketValueHistory::where('user_id', $user->id)->orderBy('matchday', 'asc')->get()
        ]);
    }

    public function myRosterPage() { $u = auth()->user(); $p = LeagueParticipant::where('user_id', $u->id)->first(); $pros = Roster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get(); return Inertia::render('Roster/Index', ['myData' => $p, 'myPlayers' => $pros]); }
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