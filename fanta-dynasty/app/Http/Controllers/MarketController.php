<?php

namespace App\Http\Controllers;

use App\Models\{RealPlayer, LeagueParticipant, Roster, League, Auction, MarketSession, Autobid};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarketController extends Controller
{
    // MOSTRA IL CALENDARIO
    public function sessions() {
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

    // SALVA LA SESSIONE (LOGICA RINFORZATA)
    public function storeSession(Request $request) {
        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();

        // 1. Validazione semplicissima
        $request->validate([
            'start_at' => 'required',
            'end_at' => 'required',
        ]);

        // 2. Creazione diretta prendendo l'ID dal server
        MarketSession::create([
            'league_id' => $participant->league_id,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
        ]);

        return back()->with('message', 'Sessione creata!');
    }

    // --- LE ALTRE FUNZIONI (NON CANCELLARLE!) ---
    public function myRosterPage() { $u = auth()->user(); $p = LeagueParticipant::where('user_id', $u->id)->first(); return Inertia::render('Roster/Index', ['myData' => $p, 'myPlayers' => Roster::where('league_id', $p->league_id)->where('user_id', $u->id)->with('player')->get()]); }
    public function auctions() { $u = auth()->user(); $p = LeagueParticipant::where('user_id', $u->id)->first(); if (!$p) return redirect()->route('dashboard'); $l = League::find($p->league_id); $this->processExpiredAuctions($l->id); $now = now(); $curr = MarketSession::where('league_id', $l->id)->where('start_at', '<=', $now)->where('end_at', '>=', $now)->first(); return Inertia::render('Market/Auctions', ['league' => $l, 'isMarketOpen' => (bool)$curr, 'currentSession' => $curr, 'myData' => $p, 'myRoster' => Roster::where('league_id', $l->id)->where('user_id', $u->id)->with('player')->get(), 'availablePlayers' => RealPlayer::whereNotIn('id', array_merge(Roster::where('league_id', $l->id)->pluck('real_player_id')->toArray(), Auction::where('league_id', $l->id)->where('is_finished', false)->pluck('real_player_id')->toArray()))->orderBy('role', 'desc')->get(), 'activeAuctions' => Auction::where('league_id', $l->id)->where('is_finished', false)->with(['player', 'user'])->get()]); }
    public function buy(Request $request) { $request->validate(['player_id' => 'required', 'price' => 'nullable|integer']); $league = League::findOrFail($request->league_id); $now = now(); $session = MarketSession::where('league_id', $league->id)->where('start_at', '<=', $now)->where('end_at', '>=', $now)->first(); if (!$session) return back()->withErrors(['error' => 'Chiuso']); $auction = Auction::where('league_id', $league->id)->where('real_player_id', $request->player_id)->where('is_finished', false)->first(); if (!$auction) { if ($now->diffInMinutes($session->end_at, false) < 90) return back()->withErrors(['error' => 'Limite 90m']); $auction = Auction::create(['league_id' => $league->id, 'real_player_id' => $request->player_id, 'user_id' => auth()->id(), 'current_bid' => 0, 'expires_at' => now()->addMinutes(90), 'is_finished' => false]); } $newBid = $request->price ?? ($auction->current_bid + 1); if ($newBid <= $auction->current_bid && $auction->current_bid > 0) return back(); $this->executeBiddingWar($auction, auth()->id(), $newBid); if ($now->diffInSeconds($auction->expires_at, false) <= 30) $auction->update(['expires_at' => $now->copy()->addMinute()]); return back(); }
    private function executeBiddingWar($auction, $lastBidderId, $lastBidAmount) { $bestOther = Autobid::where('auction_id', $auction->id)->where('user_id', '!=', $lastBidderId)->orderBy('max_bid', 'desc')->first(); if ($bestOther && $bestOther->max_bid >= $lastBidAmount) $auction->update(['user_id' => $bestOther->user_id, 'current_bid' => $lastBidAmount + 1]); else if ($lastBidAmount > $auction->current_bid) $auction->update(['user_id' => $lastBidderId, 'current_bid' => $lastBidAmount]); }
    public function release(Request $request) { $rosterItem = Roster::with('player')->findOrFail($request->roster_id); $creditRefund = ceil(($rosterItem->release_clause > 0 ? $rosterItem->release_clause : $rosterItem->purchase_price) / 2); $p = LeagueParticipant::where('league_id', $rosterItem->league_id)->where('user_id', $rosterItem->user_id)->first(); if ($p) { $p->increment('remaining_budget', $creditRefund); $p->decrement('years_budget', ($rosterItem->contract_years - floor($rosterItem->contract_years / 2))); } $rosterItem->delete(); return back(); }
    public function updateContract(Request $request) { $rosterItem = Roster::findOrFail($request->roster_id); $rosterItem->update(['contract_years' => $request->new_years, 'release_clause' => ($rosterItem->release_clause ?: $rosterItem->purchase_price) + $request->clausola_investment]); return back(); }
    public function history() { $p = LeagueParticipant::where('user_id', auth()->id())->first(); return Inertia::render('Market/History', ['league' => League::find($p->league_id), 'movements' => Roster::where('league_id', $p->league_id)->with(['player', 'user'])->orderBy('created_at', 'desc')->get()]); }
    public function closeMarketNow(League $league) { MarketSession::where('league_id', $league->id)->where('end_at', '>', now())->update(['end_at' => now()]); Auction::where('league_id', $league->id)->where('is_finished', false)->delete(); return redirect()->route('market.auctions'); }
    private function processExpiredAuctions($leagueId) { $now = now(); DB::transaction(function () use ($leagueId, $now) { $expired = Auction::where('league_id', $leagueId)->where('is_finished', false)->where('expires_at', '<=', $now)->lockForUpdate()->get(); foreach ($expired as $auc) { Roster::create(['league_id' => $auc->league_id, 'user_id' => $auc->user_id, 'real_player_id' => $auc->real_player_id, 'purchase_price' => $auc->current_bid, 'contract_years' => 1]); $p = LeagueParticipant::where('league_id', $auc->league_id)->where('user_id', $auc->user_id)->first(); if($p) $p->decrement('remaining_budget', $auc->current_bid); $auc->update(['is_finished' => true]); } }); }
}