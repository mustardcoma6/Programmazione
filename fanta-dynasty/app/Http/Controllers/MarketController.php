<?php

namespace App\Http\Controllers;

use App\Models\{RealPlayer, LeagueParticipant, Roster, League, Auction, MarketSession, Autobid};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class MarketController extends Controller
{
    public function myRosterPage() {
        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();
        if (!$participant) return redirect()->route('dashboard');

        return Inertia::render('Roster/Index', [
            'myData' => $participant,
            'myPlayers' => Roster::where('league_id', $participant->league_id)->where('user_id', $user->id)->with('player')->get()
        ]);
    }

    public function auctions() {
        $user = auth()->user();
        // Cerchiamo la lega a cui l'utente partecipa (non solo quelle che ha creato)
        $participant = LeagueParticipant::where('user_id', $user->id)->first();
        if (!$participant) return redirect()->route('dashboard');
        
        $league = League::find($participant->league_id);
        $now = now();
        
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
            'activeAuctions' => Auction::where('league_id', $league->id)->where('is_finished', false)->with(['player', 'user'])->get()
        ]);
    }

    public function buy(Request $request) {
        $request->validate(['player_id' => 'required', 'price' => 'nullable|integer', 'max_autobid' => 'nullable|integer']);
        $league = League::findOrFail($request->league_id);
        $user = auth()->user();

        $isMarketOpen = MarketSession::where('league_id', $league->id)->where('start_at', '<=', now())->where('end_at', '>=', now())->exists();
        if (!$isMarketOpen) return back()->withErrors(['error' => 'Il mercato è chiuso!']);
        $participant = LeagueParticipant::where('league_id', $league->id)
    ->where('user_id', $user->id)
    ->firstOrFail();

if ($participant->years_budget < 1) {
    return back()->withErrors([
        'error' => 'Non puoi fare offerte: non hai anni di contratto disponibili.'
    ]);
}

        $auction = Auction::where('league_id', $league->id)->where('real_player_id', $request->player_id)->where('is_finished', false)->first();

        if (!$auction) {
            // Nuova chiamata: controllo 90 min
            $session = MarketSession::where('league_id', $league->id)->where('start_at', '<=', now())->where('end_at', '>=', now())->first();
            if (now()->diffInMinutes($session->end_at, false) < 90) {
                return back()->withErrors(['error' => 'Chiamate bloccate (manca meno di 1h 30m)']);
            }
            $auction = Auction::create([
                'league_id' => $league->id, 'real_player_id' => $request->player_id, 'user_id' => $user->id,
                'current_bid' => 0, 'expires_at' => now()->addMinutes(90), 'is_finished' => false
            ]);
        }

        if ($request->max_autobid) {
            Autobid::updateOrCreate(['auction_id' => $auction->id, 'user_id' => $user->id], ['max_bid' => $request->max_autobid]);
        }

        $newBid = $request->price ?? ($auction->current_bid + 1);
        $this->executeBiddingWar($auction, $user->id, $newBid);

        $secondsLeft = now()->diffInSeconds($auction->expires_at, false);
        if ($secondsLeft <= 60 && $secondsLeft > 0) {
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

    public function release(Request $request) {
        $rosterItem = Roster::with('player')->findOrFail($request->roster_id);
        $refund = ceil(($rosterItem->release_clause > 0 ? $rosterItem->release_clause : $rosterItem->purchase_price) / 2);

$yearsRefund = floor($rosterItem->contract_years / 2);

$participant = LeagueParticipant::where('league_id', $rosterItem->league_id)
    ->where('user_id', $rosterItem->user_id)
    ->first();

$participant?->increment('remaining_budget', $refund);

if ($yearsRefund > 0) {
    $participant?->increment('years_budget', $yearsRefund);
}

$rosterItem->delete();
        return back();
    }

    public function updateContract(Request $request) {
        $rosterItem = Roster::findOrFail($request->roster_id);
        $rosterItem->update(['contract_years' => $request->new_years, 'release_clause' => ($rosterItem->release_clause ?: $rosterItem->purchase_price) + $request->clausola_investment]);
        if ($request->clausola_investment > 0) {
            LeagueParticipant::where('league_id', $rosterItem->league_id)->where('user_id', auth()->id())->first()->decrement('remaining_budget', $request->clausola_investment);
        }
        return back();
    }

    public function history() {
        $participant = LeagueParticipant::where('user_id', auth()->id())->first();
        return Inertia::render('Market/History', [
            'movements' => Roster::where('league_id', $participant->league_id)->with(['player', 'user'])->orderBy('created_at', 'desc')->get()
        ]);
    }

    public function sessions() {
        $participant = LeagueParticipant::where('user_id', auth()->id())->first();
        return Inertia::render('Market/Sessions', [
            'league' => League::find($participant->league_id),
            'sessions' => MarketSession::where('league_id', $participant->league_id)->orderBy('start_at', 'desc')->get()
        ]);
    }

    public function storeSession(Request $request) { MarketSession::create($request->all()); return back(); }
    public function closeMarketNow(League $league) {
        MarketSession::where('league_id', $league->id)->where('end_at', '>', now())->update(['end_at' => now()]);
        Auction::where('league_id', $league->id)->where('is_finished', false)->delete();
        return redirect()->route('market.auctions');
    }

    private function processExpiredAuctions($leagueId) {
        $expired = Auction::where('league_id', $leagueId)->where('is_finished', false)->where('expires_at', '<=', now())->get();
        foreach ($expired as $auc) {
            Roster::create(['league_id' => $auc->league_id, 'user_id' => $auc->user_id, 'real_player_id' => $auc->real_player_id, 'purchase_price' => $auc->current_bid, 'contract_years' => 1]);
            LeagueParticipant::where('league_id', $auc->league_id)->where('user_id', $auc->user_id)->first()?->decrement('remaining_budget', $auc->current_bid);
            LeagueParticipant::where('league_id', $auc->league_id)
    ->where('user_id', $auc->user_id)
    ->first()?->decrement('years_budget', 1);
            $auc->update(['is_finished' => true]);
        }
    }
}