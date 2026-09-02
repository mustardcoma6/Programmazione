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

        $inputTime = $request->auction_time;
        $minutes = str_contains($inputTime, ':') ? ((int)explode(':', $inputTime)[0] * 60) + (int)explode(':', $inputTime)[1] : (int)$inputTime;

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

    // CHIUSURA EMERGENZA (RIPRISTINATA)
    public function closeMarketNow() 
    {
        $p = LeagueParticipant::where('user_id', auth()->id())->first();
        if ($p) {
            // Chiude la sessione attuale impostando la fine ad "adesso"
            MarketSession::where('league_id', $p->league_id)
                ->where('end_at', '>', now())
                ->update(['end_at' => now()]);
            
            // Cancella le aste che non sono ancora finite
            Auction::where('league_id', $p->league_id)
                ->where('is_finished', false)
                ->delete();
        }
        return back()->with('message', 'Mercato chiuso e aste rimosse.');
    }

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
    
    // AZIONE DI ACQUISTO (AUTOBID A SBALZO)
    public function buy(Request $request) 
    {
        return DB::transaction(function () use ($request) {
            $l = League::findOrFail($request->league_id);
            $now = Carbon::now('Europe/Rome'); 
            $s = MarketSession::where('league_id', $l->id)->where('start_at', '<=', $now)->where('end_at', '>=', $now)->first();
            
            if (!$s) return back()->withErrors(['error' => 'Il mercato è chiuso.']);

            $user = auth()->user();
            $offertaMassimaInput = (int)$request->price;

            $a = Auction::where('league_id', $l->id)
                ->where('real_player_id', $request->player_id)
                ->where('is_finished', false)
                ->lockForUpdate()
                ->first();

            // Sincronizzazione Autobid leader attuale
            if ($a && $a->user_id) {
                Autobid::firstOrCreate(
                    ['auction_id' => $a->id, 'user_id' => $a->user_id],
                    ['max_bid' => $a->current_bid]
                );
            }

            // Controllo Budget
            $p = LeagueParticipant::where('league_id', $l->id)->where('user_id', $user->id)->first();
            if ($p->remaining_budget < $offertaMassimaInput) {
                return back()->withErrors(['error' => 'Budget insufficiente per coprire l\'asta.']);
            }

            if (!$a) {
                $a = Auction::create([
                    'league_id' => $l->id, 'real_player_id' => $request->player_id, 'user_id' => $user->id,
                    'current_bid' => 1, 'expires_at' => $now->copy()->addMinutes($s->auction_duration), 'is_finished' => false
                ]);
            } else {
                if ($offertaMassimaInput <= $a->current_bid) {
                    return back()->withErrors(['error' => 'Devi offrire più di ' . $a->current_bid]);
                }
            }

            Autobid::updateOrCreate(['auction_id' => $a->id, 'user_id' => $user->id], ['max_bid' => $offertaMassimaInput]);
            $this->executeBiddingWar($a);

            if ($now->diffInSeconds($a->expires_at, false) <= 30) {
                $a->update(['expires_at' => $now->copy()->addMinute()]);
            }

            return back();
        });
    }

    private function executeBiddingWar($a) 
    {
        $bids = Autobid::where('auction_id', $a->id)->orderBy('max_bid', 'desc')->orderBy('created_at', 'asc')->limit(2)->get();
        $vincitore = $bids[0];
        $secondo = $bids[1] ?? null;

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
    
    // --- FINANZE ---
    public function financesPage() {
        $u=auth()->user(); $lgs=$u->leagues()->get(); $fl=$lgs->first(); $lp=LeagueParticipant::where('user_id',$u->id)->where('league_id',$fl->id)->first();
        if(!$lp || $lp->games_played == 0) return Inertia::render('Societa/Finanze',['stats'=>null]);
        $inv=130; $plus=440; $calc=fn($p)=>$p<66?0:($p<70?1:2+floor(($p-70)/5));
        $bench=RealPlayer::where('role','P')->orderBy('quotation','desc')->limit(3)->get()->sum('quotation')+RealPlayer::where('role','D')->orderBy('quotation','desc')->limit(8)->get()->sum('quotation')+RealPlayer::where('role','C')->orderBy('quotation','desc')->limit(8)->get()->sum('quotation')+RealPlayer::where('role','A')->orderBy('quotation','desc')->limit(6)->get()->sum('quotation');
        $tutti=LeagueParticipant::where('league_id',$fl->id)->get(); $maxG=$tutti->map(fn($s)=>$calc($s->games_played>0?($s->total_points/$s->games_played):0))->max() ?: 1;
        $all=[]; foreach($tutti as $s){
            $rS=Roster::where('user_id',$s->user_id)->where('league_id',$s->league_id)->with('player')->get()->sum(fn($r)=>$r->player->quotation??0);
            $v=$inv+($plus*($rS/($bench?:1))*($calc($s->games_played>0?($s->total_points/$s->games_played):0)/$maxG));
            $prv=MarketValueHistory::where('user_id',$s->user_id)->where('matchday','<',$s->games_played)->orderBy('matchday','desc')->first();
            $trnd=['dir'=>'stable','perc'=>0]; if($prv && $prv->value>0){$df=$v-$prv->value;$trnd=['dir'=>$df>0?'up':($df<0?'down':'stable'),'perc'=>round(abs(($df/$prv->value)*100),1)];}
            $all[]=['user_id'=>$s->user_id,'team_name'=>$s->team_name,'valore'=>round($v,2),'trend'=>$trnd];
        }
        usort($all,fn($a,$b)=>$b['valore']<=>$a['valore']); $mio=collect($all)->firstWhere('user_id',$u->id);
        return Inertia::render('Societa/Finanze',['leagues'=>$lgs,'myData'=>$lp,'allTeams'=>$all,'history'=>MarketValueHistory::where('user_id',$u->id)->orderBy('matchday','asc')->get(),'stats'=>['valore_monetario'=>$mio['valore'],'trend'=>$mio['trend'],'asset_quality_perc'=>round((Roster::where('user_id',$u->id)->where('league_id',$fl->id)->with('player')->get()->sum(fn($r)=>$r->player->quotation??0)/($bench?:1))*100,1),'winning_efficiency_perc'=>round(($calc($lp->total_points/$lp->games_played)/$maxG)*100,1),'tuoi_gol'=>$calc($lp->total_points/$lp->games_played),'leader_gol'=>$maxG,'benchmark_val'=>$bench]]);
    }

    // --- ALTRE UTILITY ---
    public function release(Request $request) { $r=Roster::findOrFail($request->roster_id); $p=LeagueParticipant::where('user_id',$r->user_id)->first(); if($p){$p->increment('remaining_budget',ceil($r->purchase_price/2));} $r->delete(); return back(); }
    public function updateContract(Request $request) { Roster::findOrFail($request->roster_id)->update(['contract_years'=>$request->new_years]); return back(); }
    public function history() { $p=LeagueParticipant::where('user_id',auth()->id())->first(); return Inertia::render('Market/History',['league'=>League::find($p->league_id),'movements'=>Roster::where('league_id',$p->league_id)->with(['player','user'])->orderBy('created_at','desc')->get()]); }
    public function myRosterPage() { $u=auth()->user(); $p=LeagueParticipant::where('user_id',$u->id)->first(); $pros=Roster::where('league_id',$p->league_id)->where('user_id',$u->id)->with('player')->get(); return Inertia::render('Roster/Index',['myData'=>$p,'myPlayers'=>$pros]); }
    public function primaveraPage() { $u=auth()->user(); $p=LeagueParticipant::where('user_id',$u->id)->first(); $players=PrimaveraRoster::where('league_id',$p->league_id)->where('user_id',$u->id)->with('player')->get(); return Inertia::render('Societa/Primavera',['myData'=>$p,'primaveraPlayers'=>$players]); }
    
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