<?php

namespace App\Http\Controllers;

use App\Models\{RealPlayer, LeagueParticipant, Roster, PrimaveraRoster, League, Auction, MarketSession, Autobid, MarketValueHistory};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarketController extends Controller
{
    // VISUALIZZAZIONE ASTE
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

    // CHIAMA GIOCATORE / PUNTA
    public function buy(Request $request) 
    {
        return DB::transaction(function () use ($request) {
            $l = League::findOrFail($request->league_id);
            $now = Carbon::now('Europe/Rome'); 
            
            $s = MarketSession::where('league_id', $l->id)
                ->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now)
                ->first();
                
            if (!$s) return back()->withErrors(['error' => 'Mercato chiuso.']);

            $user = auth()->user();
            $maxBidUtente = (int)$request->price;

            $a = Auction::where('league_id', $l->id)
                ->where('real_player_id', $request->player_id)
                ->where('is_finished', false)
                ->lockForUpdate()
                ->first();

            // Sincronizzazione Autobid leader attuale
            if ($a && $a->user_id) {
                Autobid::firstOrCreate(['auction_id' => $a->id, 'user_id' => $a->user_id], ['max_bid' => $a->current_bid]);
            }

            // Controllo Budget
            $p = LeagueParticipant::where('league_id', $l->id)->where('user_id', $user->id)->first();
            if ($p->remaining_budget < $maxBidUtente) {
                return back()->withErrors(['error' => 'Budget insufficiente.']);
            }

            if (!$a) {
                // CALCOLO SCADENZA ASTA (Senza il blocco dei 90 minuti)
                // Se la durata scelta (es. 10 min) supera la fine del mercato, 
                // l'asta scade esattamente quando chiude il mercato.
                $scadenzaTeorica = $now->copy()->addMinutes($s->auction_duration);
                $fineMercato = Carbon::parse($s->end_at);
                
                $scadenzaEffettiva = $scadenzaTeorica->gt($fineMercato) ? $fineMercato : $scadenzaTeorica;

                $a = Auction::create([
                    'league_id' => $l->id,
                    'real_player_id' => $request->player_id,
                    'user_id' => $user->id,
                    'current_bid' => 1,
                    'expires_at' => $scadenzaEffettiva,
                    'is_finished' => false
                ]);
            } else {
                if ($maxBidUtente <= $a->current_bid) {
                    return back()->withErrors(['error' => 'Offri più di ' . $a->current_bid]);
                }
            }

            Autobid::updateOrCreate(['auction_id' => $a->id, 'user_id' => $user->id], ['max_bid' => $maxBidUtente]);
            $this->executeBiddingWar($a);

            // Estensione tempo (solo se non ha già superato la fine del mercato)
            if ($now->diffInSeconds($a->expires_at, false) <= 30) {
                $nuovaScadenza = $a->expires_at->addMinute();
                $fineMercato = Carbon::parse($s->end_at);
                if ($nuovaScadenza->lt($fineMercato)) {
                    $a->update(['expires_at' => $nuovaScadenza]);
                }
            }

            return back();
        });
    }

    private function executeBiddingWar($a) 
    {
        $bids = Autobid::where('auction_id', $a->id)->orderBy('max_bid', 'desc')->orderBy('created_at', 'asc')->limit(2)->get();
        $vincitore = $bids[0]; $secondo = $bids[1] ?? null;
        if (!$secondo) {
            $a->update(['user_id' => $vincitore->user_id, 'current_bid' => max(1, $a->current_bid)]);
        } else {
            if ($vincitore->max_bid > $secondo->max_bid) {
                $a->update(['user_id' => $vincitore->user_id, 'current_bid' => $secondo->max_bid + 1]);
            } else {
                $a->update(['user_id' => $vincitore->user_id, 'current_bid' => $vincitore->max_bid]);
            }
        }
    }

    // GESTIONE SESSIONI
    public function sessions() { 
        $p = LeagueParticipant::where('user_id', auth()->id())->first();
        return Inertia::render('Market/Sessions', [
            'league' => League::find($p->league_id),
            'sessions' => MarketSession::where('league_id', $p->league_id)->orderBy('start_at', 'desc')->get()
        ]); 
    }

    public function storeSession(Request $request) 
    {
        $p = LeagueParticipant::where('user_id', auth()->id())->first();
        $input = $request->auction_time;
        // Se scrivi "10" lo trasforma in 10, se "01:30" in 90
        $min = str_contains($input, ':') ? (explode(':', $input)[0]*60 + explode(':', $input)[1]) : (int)$input;
        
        MarketSession::create([
            'league_id' => $p->league_id,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'auction_duration' => $min ?: 5, // Se vuoto mette 5 min
            'allowed_roles' => implode(',', $request->roles)
        ]);
        return back()->with('message', 'Sessione creata!');
    }

    // --- ALTRE FUNZIONI INVARIATE ---
    public function closeMarketNow() { $p = LeagueParticipant::where('user_id', auth()->id())->first(); if ($p) { MarketSession::where('league_id', $p->league_id)->where('end_at', '>', now())->update(['end_at' => now()]); Auction::where('league_id', $p->league_id)->where('is_finished', false)->delete(); } return back(); }
    public function financesPage() { /* ... logica finanze che abbiamo già fatto ... */ }
    public function myRosterPage() { $u = auth()->user(); $p = LeagueParticipant::where('user_id', $u->id)->first(); $pros = Roster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get(); return Inertia::render('Roster/Index', ['myData' => $p, 'myPlayers' => $pros]); }
    public function release(Request $request) { $r = Roster::findOrFail($request->roster_id); $p = LeagueParticipant::where('user_id', $r->user_id)->first(); if($p){ $p->increment('remaining_budget', ceil($r->purchase_price/2)); } $r->delete(); return back(); }
    public function updateContract(Request $request) { Roster::findOrFail($request->roster_id)->update(['contract_years' => $request->new_years]); return back(); }
    public function history() { $p = LeagueParticipant::where('user_id', auth()->id())->first(); return Inertia::render('Market/History', ['league' => League::find($p->league_id), 'movements' => Roster::where('league_id', $p->league_id)->with(['player', 'user'])->orderBy('created_at', 'desc')->get()]); }
    public function primaveraPage() { $u = auth()->user(); $p = LeagueParticipant::where('user_id', $u->id)->first(); $players = PrimaveraRoster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get(); return Inertia::render('Societa/Primavera', ['myData' => $p, 'primaveraPlayers' => $players]); }
    private function processExpiredAuctions($leagueId) { $now = Carbon::now('Europe/Rome'); DB::transaction(function () use ($leagueId, $now) { $expired = Auction::where('league_id', $leagueId)->where('is_finished', false)->where('expires_at', '<=', $now)->lockForUpdate()->get(); foreach ($expired as $auc) { Roster::create(['league_id' => $auc->league_id, 'user_id' => $auc->user_id, 'real_player_id' => $auc->real_player_id, 'purchase_price' => $auc->current_bid, 'contract_years' => 1]); $p = LeagueParticipant::where('league_id', $auc->league_id)->where('user_id', $auc->user_id)->first(); if ($p) { $p->decrement('remaining_budget', $auc->current_bid); $p->decrement('years_budget', 1); } $auc->update(['is_finished' => true]); } }); }
}