<?php

namespace App\Http\Controllers;

use App\Models\{RealPlayer, LeagueParticipant, Roster, PrimaveraRoster, League, Auction, MarketSession, Autobid};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarketController extends Controller
{
    public function financesPage() {
        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();
        if (!$participant) return redirect()->route('dashboard');

        $league = League::find($participant->league_id);
        
        // 1. DATABASE VALORI IN EURO (Dati forniti dal Pres)
        $euroValues = [
            'SAO PAULO' => 68.12,
            'ATLETICO G MINEIRO' => 59.54,
            'SANTOS' => 56.94,
            'FLAMENGO' => 45.24,
            'CRUZEIRO E.C.' => 40.82,
            'PALMEIRAS' => 39.00,
            'CORINTHIANS' => 39.00,
            'VASCO DE GAMA' => 33.28,
            'BOTAFOGO' => 33.02,
            'FLUMINENSE' => 30.94
        ];

        // Cerchiamo il valore per la squadra dell'utente loggato
        $myTeamName = strtoupper(trim($participant->team_name));
        $myEuroValue = $euroValues[$myTeamName] ?? 0.00;

        // 2. LOGICA RANKING QUOTAZIONI (Esistente)
        $allTeams = LeagueParticipant::where('league_id', $league->id)->with('user')->get();
        foreach ($allTeams as $team) {
            $proValue = DB::table('rosters')
                ->join('real_players', 'rosters.real_player_id', '=', 'real_players.id')
                ->where('rosters.user_id', $team->user_id)
                ->where('rosters.league_id', $league->id)
                ->sum('real_players.quotation');

            $primaveraValue = DB::table('primavera_rosters')
                ->join('real_players', 'primavera_rosters.real_player_id', '=', 'real_players.id')
                ->where('primavera_rosters.user_id', $team->user_id)
                ->where('primavera_rosters.league_id', $league->id)
                ->sum('real_players.quotation');

            $team->total_quotation_value = (int)$proValue + (int)$primaveraValue;
        }

        $sortedTeams = $allTeams->sortByDesc('total_quotation_value')->values()->all();

        return Inertia::render('Societa/Finances', [
            'myData' => $participant,
            'ranking' => $sortedTeams,
            'myEuroValue' => $myEuroValue // Passiamo il valore in Euro
        ]);
    }

    // --- MANTENIAMO TUTTE LE ALTRE FUNZIONI INTEGRALI ---
    public function myRosterPage() { $u = auth()->user(); $p = LeagueParticipant::where('user_id', $u->id)->first(); if (!$p) return redirect()->route('dashboard'); $pros = Roster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get()->map(function($i){$i->is_primavera=false; return $i;}); $juniors = PrimaveraRoster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get()->map(function($i){$i->is_primavera=true; return $i;}); $merged = $pros->concat($juniors)->sortBy([function($a,$b){$o=['P'=>1,'D'=>2,'C'=>3,'A'=>4]; return $o[$a->player->role]<=>$o[$b->player->role];},['player.name','asc']])->values()->all(); $val = $pros->sum('purchase_price') + $juniors->sum('purchase_price'); return Inertia::render('Roster/Index', ['myData' => $p, 'myPlayers' => $merged, 'rosterValue' => (int)$val]); }
    public function auctions() { $u = auth()->user(); $p = LeagueParticipant::where('user_id', $u->id)->first(); if (!$p) return redirect()->route('dashboard'); $l = League::find($p->league_id); $this->processExpiredAuctions($l->id); $now = now(); $curr = MarketSession::where('league_id', $l->id)->where('start_at', '<=', $now)->where('end_at', '>=', $now)->first(); return Inertia::render('Market/Auctions', ['league' => $l, 'isMarketOpen' => (bool)$curr, 'currentSession' => $curr, 'myData' => $p, 'frozenCredits' => (int)(Auction::where('league_id', $l->id)->where('user_id', $u->id)->where('is_finished', false)->sum('current_bid') ?? 0), 'myRoster' => Roster::where('league_id', $l->id)->where('user_id', $u->id)->with('player')->get(), 'availablePlayers' => RealPlayer::whereNotIn('id', array_merge(Roster::where('league_id', $l->id)->pluck('real_player_id')->toArray(), PrimaveraRoster::where('league_id', $l->id)->pluck('real_player_id')->toArray()))->orderBy('role', 'desc')->get(), 'activeAuctions' => Auction::where('league_id', $l->id)->where('is_finished', false)->with(['player', 'user'])->get()]); }
    public function buy(Request $request) { $request->validate(['player_id' => 'required', 'price' => 'nullable|integer']); $l = League::findOrFail($request->league_id); $now = Carbon::now('Europe/Rome'); $s = MarketSession::where('league_id', $l->id)->where('start_at', '<=', $now)->where('end_at', '>=', $now)->first(); if (!$s) return back(); $a = Auction::where('league_id', $l->id)->where('real_player_id', $request->player_id)->where('is_finished', false)->first(); if (!$a) { $a = Auction::create(['league_id' => $l->id, 'real_player_id' => $request->player_id, 'user_id' => auth()->id(), 'current_bid' => 0, 'expires_at' => now()->copy()->addMinutes($s->auction_duration), 'is_finished' => false]); } $nb = $request->price ?? ($a->current_bid + 1); $this->executeBiddingWar($a, auth()->id(), $nb); if ($now->diffInSeconds($a->expires_at, false) <= 30) $a->update(['expires_at' => now()->copy()->addMinute()]); return back(); }
    private function executeBiddingWar($a, $lId, $lA) { $bo = Autobid::where('auction_id', $a->id)->where('user_id', '!=', $lId)->orderBy('max_bid', 'desc')->orderBy('created_at', 'asc')->first(); if ($bo && $bo->max_bid >= $lA) $a->update(['user_id' => $bo->user_id, 'current_bid' => $lA + 1]); elseif ($bo && $bo->max_bid == $lA) $a->update(['user_id' => $bo->user_id, 'current_bid' => $lA]); else $a->update(['user_id' => $lId, 'current_bid' => $lA]); }
    public function release(Request $request) { $r = Roster::with('player')->findOrFail($request->roster_id); $p = LeagueParticipant::where('league_id', $r->league_id)->where('user_id', $r->user_id)->first(); if ($p) { $p->increment('remaining_budget', ceil(($r->release_clause ?: $r->purchase_price) / 2)); $p->decrement('years_budget', ($r->contract_years - floor($r->contract_years / 2))); } $r->delete(); return back(); }
    public function updateContract(Request $request) { $r = Roster::findOrFail($request->roster_id); $r->update(['contract_years' => $request->new_years, 'release_clause' => ($r->release_clause ?: $r->purchase_price) + $request->clausola_investment]); return back(); }
    public function history() { $p = LeagueParticipant::where('user_id', auth()->id())->first(); return Inertia::render('Market/History', ['league' => League::find($p->league_id), 'movements' => Roster::where('league_id', $p->league_id)->with(['player', 'user'])->orderBy('created_at', 'desc')->get()]); }
    public function primaveraPage() { $u = auth()->user(); $p = LeagueParticipant::where('user_id', $u->id)->first(); $players = PrimaveraRoster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get(); return Inertia::render('Societa/Primavera', ['myData' => $p, 'primaveraPlayers' => $players]); }
    public function sessions() { $p = LeagueParticipant::where('user_id', auth()->id())->first(); return Inertia::render('Market/Sessions', ['league' => League::find($p->league_id), 'sessions' => MarketSession::where('league_id', $p->league_id)->orderBy('start_at', 'desc')->get()]); }
    public function storeSession(Request $request) { $p = LeagueParticipant::where('user_id', auth()->id())->first(); $t = explode(':', $request->auction_time); $tm = ($t[0] * 60) + $t[1]; MarketSession::create(['league_id' => $p->league_id, 'start_at' => $request->start_at, 'end_at' => $request->end_at, 'auction_duration' => $tm, 'allowed_roles' => implode(',', $request->roles)]); return back(); }
    public function closeMarketNow(League $l) { MarketSession::where('league_id', $l->id)->where('end_at', '>', now())->update(['end_at' => now()]); Auction::where('league_id', $l->id)->where('is_finished', false)->delete(); return redirect()->route('market.auctions'); }
    private function processExpiredAuctions($lid) { $now = Carbon::now('Europe/Rome'); DB::transaction(function () use ($lid, $now) { $ex = Auction::where('league_id', $lid)->where('is_finished', false)->where('expires_at', '<=', $now)->lockForUpdate()->get(); foreach ($ex as $auc) { Roster::create(['league_id' => $auc->league_id, 'user_id' => $auc->user_id, 'real_player_id' => $auc->real_player_id, 'purchase_price' => $auc->current_bid, 'contract_years' => 1]); $p = LeagueParticipant::where('league_id', $auc->league_id)->where('user_id', $auc->user_id)->first(); if($p) $p->decrement('remaining_budget', $auc->current_bid); $auc->update(['is_finished' => true]); } }); }
}