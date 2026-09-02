<?php

namespace App\Http\Controllers;

use App\Models\{RealPlayer, LeagueParticipant, Roster, PrimaveraRoster, League, Auction, MarketSession, Autobid, MarketValueHistory};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarketController extends Controller
{
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

    public function buy(Request $request) 
    {
        return DB::transaction(function () use ($request) {
            $l = League::findOrFail($request->league_id);
            $now = Carbon::now('Europe/Rome'); 
            $s = MarketSession::where('league_id', $l->id)->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now)->first();
                
            if (!$s) return back()->withErrors(['error' => 'Il mercato è chiuso.']);

            $user = auth()->user();
            $maxBidUtente = (int)$request->price;

            // 1. Trova o crea l'asta
            $a = Auction::where('league_id', $l->id)
                ->where('real_player_id', $request->player_id)
                ->where('is_finished', false)
                ->lockForUpdate() // Fondamentale per evitare rilanci incrociati
                ->first();

            // 2. Controllo Budget
            $p = LeagueParticipant::where('league_id', $l->id)->where('user_id', $user->id)->first();
            if ($p->remaining_budget < $maxBidUtente) {
                return back()->withErrors(['error' => 'Non hai abbastanza crediti per questa offerta massima.']);
            }

            if (!$a) {
                // Nuova asta: il prezzo parte da 1
                $a = Auction::create([
                    'league_id' => $l->id,
                    'real_player_id' => $request->player_id,
                    'user_id' => $user->id,
                    'current_bid' => 1,
                    'expires_at' => $now->copy()->addMinutes($s->auction_duration),
                    'is_finished' => false
                ]);
            } else {
                // Se l'asta esiste, il nuovo limite deve essere superiore al prezzo attuale
                if ($maxBidUtente <= $a->current_bid) {
                    return back()->withErrors(['error' => 'Devi offrire più del prezzo attuale ('.$a->current_bid.').']);
                }
            }

            // 3. Salva il limite massimo (Autobid)
            Autobid::updateOrCreate(
                ['auction_id' => $a->id, 'user_id' => $user->id],
                ['max_bid' => $maxBidUtente]
            );

            // 4. Esegui la guerra tra i massimi
            $this->executeBiddingWar($a);

            // 5. Bonus tempo
            if ($now->diffInSeconds($a->expires_at, false) <= 30) {
                $a->update(['expires_at' => $now->copy()->addMinute()]);
            }

            return back();
        });
    }

    private function executeBiddingWar($a) 
    {
        // Prende i due Autobid più alti
        $bids = Autobid::where('auction_id', $a->id)
            ->orderBy('max_bid', 'desc')
            ->orderBy('created_at', 'asc') // In caso di parità vince chi ha puntato prima
            ->limit(2)
            ->get();

        $vincitore = $bids[0];
        $secondo = $bids[1] ?? null;

        if (!$secondo) {
            // Solo un offerente: il prezzo resta quello di chiamata (minimo 1)
            $a->update([
                'user_id' => $vincitore->user_id,
                'current_bid' => max(1, $a->current_bid)
            ]);
        } else {
            // Sfida tra due o più
            if ($vincitore->max_bid > $secondo->max_bid) {
                // Il vincitore supera il secondo di 1
                $a->update([
                    'user_id' => $vincitore->user_id,
                    'current_bid' => $secondo->max_bid + 1
                ]);
            } else {
                // Parità perfetta: il prezzo va al massimo comune, vince il primo (vincitore)
                $a->update([
                    'user_id' => $vincitore->user_id,
                    'current_bid' => $vincitore->max_bid
                ]);
            }
        }
    }

    // --- LE ALTRE FUNZIONI (Sessions, Finances, Roster...) ---
    public function sessions() { $user = auth()->user(); $p = LeagueParticipant::where('user_id', $user->id)->first(); $league = League::find($p->league_id); return Inertia::render('Market/Sessions', ['league' => $league, 'sessions' => MarketSession::where('league_id', $league->id)->orderBy('start_at', 'desc')->get()]); }
    public function storeSession(Request $request) { $p = LeagueParticipant::where('user_id', auth()->id())->first(); $inputTime = $request->auction_time; $minutes = str_contains($inputTime, ':') ? ((int)explode(':', $inputTime)[0] * 60) + (int)explode(':', $inputTime)[1] : (int)$inputTime; MarketSession::create(['league_id' => $p->league_id, 'start_at' => $request->start_at, 'end_at' => $request->end_at, 'auction_duration' => $minutes ?: 5, 'allowed_roles' => implode(',', $request->roles)]); return back(); }
    public function closeMarketNow() { $p = LeagueParticipant::where('user_id', auth()->id())->first(); if ($p) { MarketSession::where('league_id', $p->league_id)->where('end_at', '>', now())->update(['end_at' => now()]); Auction::where('league_id', $p->league_id)->where('is_finished', false)->delete(); } return back(); }
    public function myRosterPage() { $u = auth()->user(); $p = LeagueParticipant::where('user_id', $u->id)->first(); $pros = Roster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get(); return Inertia::render('Roster/Index', ['myData' => $p, 'myPlayers' => $pros]); }
    public function release(Request $request) { $r = Roster::findOrFail($request->roster_id); $p = LeagueParticipant::where('user_id', $r->user_id)->first(); if($p){ $p->increment('remaining_budget', ceil($r->purchase_price/2)); } $r->delete(); return back(); }
    public function updateContract(Request $request) { Roster::findOrFail($request->roster_id)->update(['contract_years' => $request->new_years]); return back(); }
    public function history() { $p = LeagueParticipant::where('user_id', auth()->id())->first(); return Inertia::render('Market/History', ['league' => League::find($p->league_id), 'movements' => Roster::where('league_id', $p->league_id)->with(['player', 'user'])->orderBy('created_at', 'desc')->get()]); }
    public function primaveraPage() { $u = auth()->user(); $p = LeagueParticipant::where('user_id', $u->id)->first(); $players = PrimaveraRoster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get(); return Inertia::render('Societa/Primavera', ['myData' => $p, 'primaveraPlayers' => $players]); }

    public function financesPage()
    {
        $user = auth()->user(); $leagues = $user->leagues()->get(); $firstLeague = $leagues->first(); $lp = LeagueParticipant::where('user_id', $user->id)->where('league_id', $firstLeague->id)->first();
        if (!$lp || $lp->games_played == 0) return Inertia::render('Societa/Finanze', ['stats' => null]);
        $inv = 130; $plus = 440; $calcolaGol = function($p){ if($p<66)return 0; if($p<70)return 1; return 2+floor(($p-70)/5); };
        $topP = RealPlayer::where('role','P')->orderBy('quotation','desc')->limit(3)->get()->sum('quotation');
        $topD = RealPlayer::where('role','D')->orderBy('quotation','desc')->limit(8)->get()->sum('quotation');
        $topC = RealPlayer::where('role','C')->orderBy('quotation','desc')->limit(8)->get()->sum('quotation');
        $topA = RealPlayer::where('role','A')->orderBy('quotation','desc')->limit(6)->get()->sum('quotation');
        $bench = ($topP+$topD+$topC+$topA) ?: 1;
        $tutti = LeagueParticipant::where('league_id', $firstLeague->id)->get();
        $maxGol = $tutti->map(fn($s)=>$calcolaGol($s->games_played>0 ? ($s->total_points/$s->games_played) : 0))->max() ?: 1;
        $allTeams = [];
        foreach($tutti as $s){
            $rSum = Roster::where('user_id',$s->user_id)->where('league_id',$s->league_id)->with('player')->get()->sum(fn($r)=>$r->player->quotation??0);
            $val = $inv + ($plus * ($rSum/$bench) * ($calcolaGol($s->games_played>0?($s->total_points/$s->games_played):0)/$maxGol));
            $prev = MarketValueHistory::where('user_id',$s->user_id)->where('matchday','<',$s->games_played)->orderBy('matchday','desc')->first();
            $trend = ['dir'=>'stable','perc'=>0];
            if($prev && $prev->value>0){ $diff=$val-$prev->value; $trend=['dir'=>$diff>0?'up':($diff<0?'down':'stable'), 'perc'=>round(abs(($diff/$prev->value)*100),1)]; }
            $allTeams[] = ['user_id'=>$s->user_id,'team_name'=>$s->team_name,'valore'=>round($val,2),'trend'=>$trend];
        }
        usort($allTeams, fn($a, $b) => $b['valore'] <=> $a['valore']);
        $mioDato = collect($allTeams)->firstWhere('user_id', $user->id);
        return Inertia::render('Societa/Finanze', [
            'leagues'=>$leagues,'myData'=>$lp,'allTeams'=>$allTeams,'history'=>MarketValueHistory::where('user_id',$user->id)->orderBy('matchday','asc')->get(),
            'stats'=>['valore_monetario'=>$mioDato['valore'],'trend'=>$mioDato['trend'],'asset_quality_perc'=>round((Roster::where('user_id',$user->id)->with('player')->get()->sum(fn($r)=>$r->player->quotation??0)/$bench)*100,1),'winning_efficiency_perc'=>round(($calcolaGol($lp->total_points/$lp->games_played)/$maxGol)*100,1),'tuoi_gol'=>$calcolaGol($lp->total_points/$lp->games_played),'leader_gol'=>$maxGol,'benchmark_val'=>$bench]
        ]);
    }

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