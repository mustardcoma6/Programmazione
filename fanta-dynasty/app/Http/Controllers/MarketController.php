<?php

namespace App\Http\Controllers;

use App\Models\{RealPlayer, LeagueParticipant, Roster, League, Auction, MarketSession, Autobid};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarketController extends Controller
{
    // --- PAGINA ROSA (Gestione Contratti) ---
    public function myRosterPage() {
        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();
        if (!$participant) return redirect()->route('dashboard');

        return Inertia::render('Roster/Index', [
            'myData' => $participant,
            'myPlayers' => Roster::where('league_id', $participant->league_id)
                                ->where('user_id', $user->id)
                                ->with('player')
                                ->get()
        ]);
    }

    // --- PAGINA CALCIOMERCATO (Aste e Svincoli) ---
    public function auctions() {
        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();
        if (!$participant) return redirect()->route('dashboard');
        
        $league = League::find($participant->league_id);
        $this->processExpiredAuctions($league->id);

        $now = Carbon::now('Europe/Rome');
        $currentSession = MarketSession::where('league_id', $league->id)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->first();

        $myRoster = Roster::where('league_id', $league->id)->where('user_id', $user->id)->with('player')->get();
        $soldIds = Roster::where('league_id', $league->id)->pluck('real_player_id')->toArray();
        $auctionedIds = Auction::where('league_id', $league->id)->where('is_finished', false)->pluck('real_player_id')->toArray();
        $availablePlayers = RealPlayer::whereNotIn('id', array_merge($soldIds, $auctionedIds))->orderBy('role', 'desc')->get();

        return Inertia::render('Market/Auctions', [
            'league' => $league,
            'isMarketOpen' => (bool)$currentSession,
            'currentSession' => $currentSession,
            'myData' => $participant,
            'myRoster' => $myRoster,
            'availablePlayers' => $availablePlayers,
            'activeAuctions' => Auction::where('league_id', $league->id)
                                ->where('is_finished', false)
                                ->with(['player', 'user'])
                                ->get()
        ]);
    }

    // --- AZIONE: PUNTA / CHIAMA ---
    public function buy(Request $request) {
        $request->validate(['player_id' => 'required', 'price' => 'nullable|integer', 'max_autobid' => 'nullable|integer']);
        $league = League::findOrFail($request->league_id);
        $now = Carbon::now('Europe/Rome');
        
        $session = MarketSession::where('league_id', $league->id)->where('start_at', '<=', $now)->where('end_at', '>=', $now)->first();
        if (!$session) return back()->withErrors(['error' => 'Mercato chiuso!']);

        $auction = Auction::where('league_id', $league->id)->where('real_player_id', $request->player_id)->where('is_finished', false)->first();

        if (!$auction) {
            if ($now->diffInMinutes($session->end_at, false) < 90) {
                return back()->withErrors(['error' => 'Chiamate bloccate (manca meno di 1h 30m)']);
            }
            $auction = Auction::create([
                'league_id' => $league->id, 'real_player_id' => $request->player_id, 'user_id' => auth()->id(),
                'current_bid' => 0, 'expires_at' => $now->addMinutes(90), 'is_finished' => false
            ]);
        }

        if ($request->max_autobid) {
            Autobid::updateOrCreate(['auction_id' => $auction->id, 'user_id' => auth()->id()], ['max_bid' => $request->max_autobid]);
        }

        $newBid = $request->price ?? ($auction->current_bid + 1);
        $this->executeBiddingWar($auction, auth()->id(), $newBid);

        if ($now->diffInSeconds($auction->expires_at, false) <= 60) {
            $auction->update(['expires_at' => $auction->expires_at->addSeconds(30)]);
        }

        return back();
    }

    private function executeBiddingWar($auction, $lastBidderId, $lastBidAmount) {
        $bestOther = Autobid::where('auction_id', $auction->id)->where('user_id', '!=', $lastBidderId)->orderBy('max_bid', 'desc')->first();
        if ($bestOther && $bestOther->max_bid >= $lastBidAmount) {
            $auction->update(['user_id' => $bestOther->user_id, 'current_bid' => $lastBidAmount + 1]);
        } else {
            if ($lastBidAmount > $auction->current_bid) {
                $auction->update(['user_id' => $lastBidderId, 'current_bid' => $lastBidAmount]);
            }
        }
    }

    // --- AZIONE: SVINCOLA ---
    public function release(Request $request) {
        $rosterItem = Roster::with('player')->findOrFail($request->roster_id);
        $baseValue = $rosterItem->release_clause > 0 ? $rosterItem->release_clause : $rosterItem->purchase_price;
        $refund = ceil($baseValue / 2);

        $participant = LeagueParticipant::where('league_id', $rosterItem->league_id)->where('user_id', $rosterItem->user_id)->first();
        if ($participant) $participant->increment('remaining_budget', $refund);
        
        $rosterItem->delete();
        return back();
    }

    // --- AZIONE: AGGIORNA CONTRATTO ---
    public function updateContract(Request $request) {
        $request->validate(['roster_id' => 'required', 'new_years' => 'required|integer|min:1', 'clausola_investment' => 'required|integer|min:0']);
        $rosterItem = Roster::findOrFail($request->roster_id);
        $participant = LeagueParticipant::where('league_id', $rosterItem->league_id)->where('user_id', auth()->id())->first();
        
        $currentValue = $rosterItem->release_clause > 0 ? $rosterItem->release_clause : $rosterItem->purchase_price;
        $rosterItem->update([
            'contract_years' => $request->new_years,
            'release_clause' => $currentValue + $request->clausola_investment
        ]);

        if ($request->clausola_investment > 0) {
            $participant->decrement('remaining_budget', $request->clausola_investment);
        }
        return back();
    }

    public function history() {
        $p = LeagueParticipant::where('user_id', auth()->id())->first();
        return Inertia::render('Market/History', ['league' => League::find($p->league_id), 'movements' => Roster::where('league_id', $p->league_id)->with(['player', 'user'])->orderBy('created_at', 'desc')->get()]);
    }

    public function sessions() {
        $p = LeagueParticipant::where('user_id', auth()->id())->first();
        return Inertia::render('Market/Sessions', ['league' => League::find($p->league_id), 'sessions' => MarketSession::where('league_id', $p->league_id)->orderBy('start_at', 'desc')->get()]);
    }

    public function storeSession(Request $request) { MarketSession::create($request->all()); return back(); }
    public function closeMarketNow(League $league) {
        MarketSession::where('league_id', $league->id)->where('end_at', '>', now())->update(['end_at' => now()]);
        Auction::where('league_id', $league->id)->where('is_finished', false)->delete();
        return redirect()->route('market.auctions');
    }

    private function processExpiredAuctions($leagueId) {
        $now = Carbon::now('Europe/Rome');
        DB::transaction(function () use ($leagueId, $now) {
            $expired = Auction::where('league_id', $leagueId)->where('is_finished', false)->where('expires_at', '<=', $now)->lockForUpdate()->get();
            foreach ($expired as $auc) {
                Roster::create(['league_id' => $auc->league_id, 'user_id' => $auc->user_id, 'real_player_id' => $auc->real_player_id, 'purchase_price' => $auc->current_bid, 'contract_years' => 1]);
                $p = LeagueParticipant::where('league_id', $auc->league_id)->where('user_id', $auc->user_id)->first();
                if($p) $p->decrement('remaining_budget', $auc->current_bid);
                $auc->update(['is_finished' => true]);
            }
        });
    }
}