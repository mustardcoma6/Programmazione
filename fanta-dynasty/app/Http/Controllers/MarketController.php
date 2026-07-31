<?php

namespace App\Http\Controllers;

use App\Models\{RealPlayer, LeagueParticipant, Roster, League, Auction, MarketSession, Autobid};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarketController extends Controller
{
    // --- 1. PAGINA ROSA ---
    public function myRosterPage() {
        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();
        if (!$participant) return redirect()->route('dashboard');

        return Inertia::render('Roster/Index', [
            'myData' => $participant,
            'myPlayers' => Roster::where('league_id', $participant->league_id)->where('user_id', $user->id)->with('player')->get()
        ]);
    }

    // --- 2. PAGINA CALCIOMERCATO ---
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

        $frozenCredits = Auction::where('league_id', $league->id)->where('user_id', $user->id)->where('is_finished', false)->sum('current_bid') ?? 0;
        $myRoster = Roster::where('league_id', $league->id)->where('user_id', $user->id)->with('player')->get();
        $soldIds = Roster::where('league_id', $league->id)->pluck('real_player_id')->toArray();
        $auctionedIds = Auction::where('league_id', $league->id)->where('is_finished', false)->pluck('real_player_id')->toArray();
        $availablePlayers = RealPlayer::whereNotIn('id', array_merge($soldIds, $auctionedIds))->orderBy('role', 'desc')->get();

        return Inertia::render('Market/Auctions', [
            'league' => $league,
            'isMarketOpen' => (bool)$currentSession,
            'currentSession' => $currentSession,
            'myData' => $participant,
            'frozenCredits' => (int)$frozenCredits,
            'myRoster' => $myRoster,
            'availablePlayers' => $availablePlayers,
            'activeAuctions' => Auction::where('league_id', $league->id)->where('is_finished', false)->with(['player', 'user'])->get()
        ]);
    }

    // --- 3. AZIONE ACQUISTO / RILANCIO ---
    public function buy(Request $request) {
        $request->validate(['player_id' => 'required', 'price' => 'nullable|integer', 'max_autobid' => 'nullable|integer']);
        $user = auth()->user();
        $now = Carbon::now('Europe/Rome');
        $participant = LeagueParticipant::where('user_id', $user->id)->first();
        
        $session = MarketSession::where('league_id', $participant->league_id)->where('start_at', '<=', $now)->where('end_at', '>=', $now)->first();
        if (!$session) return back()->withErrors(['error' => 'Il mercato è chiuso!']);

        $auction = Auction::where('league_id', $participant->league_id)->where('real_player_id', $request->player_id)->where('is_finished', false)->first();

        if (!$auction) {
            $allowed = explode(',', $session->allowed_roles);
            $player = RealPlayer::findOrFail($request->player_id);
            if (!in_array($player->role, $allowed)) return back()->withErrors(['error' => "Ruolo non ammesso in questa sessione."]);
            if ($now->diffInMinutes($session->end_at, false) < $session->auction_duration) return back()->withErrors(['error' => "Tempo insufficiente."]);

            $auction = Auction::create(['league_id' => $participant->league_id, 'real_player_id' => $player->id, 'user_id' => $user->id, 'current_bid' => 0, 'expires_at' => $now->copy()->addMinutes($session->auction_duration), 'is_finished' => false]);
        }

        if ($request->max_autobid) Autobid::updateOrCreate(['auction_id' => $auction->id, 'user_id' => $user->id], ['max_bid' => $request->max_autobid]);
        $newBid = $request->price ?? ($auction->current_bid + 1);
        if ($newBid <= $auction->current_bid && $auction->current_bid > 0) return back()->withErrors(['error' => "Offerta minima: " . ($auction->current_bid + 1)]);

        $this->executeBiddingWar($auction, $user->id, $newBid);
        if ($now->diffInSeconds($auction->expires_at, false) <= 30) $auction->update(['expires_at' => $now->copy()->addMinute()]);
        return back();
    }

    // --- 4. GESTIONE SESSIONI (ADMIN) ---
    public function sessions() {
        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();
        if (!$participant) return redirect()->route('dashboard');
        $league = League::find($participant->league_id);
        $sessions = MarketSession::where('league_id', $league->id)->orderBy('start_at', 'desc')->get();
        return Inertia::render('Market/Sessions', ['league' => $league, 'sessions' => $sessions]);
    }

    public function storeSession(Request $request) {
        $participant = LeagueParticipant::where('user_id', auth()->id())->first();
        $request->validate(['start_at' => 'required|date', 'end_at' => 'required|date|after:start_at', 'auction_time' => 'required', 'roles' => 'required|array']);

        $timeParts = explode(':', $request->auction_time);
        $totalMinutes = ($timeParts[0] * 60) + $timeParts[1];

        MarketSession::create([
            'league_id' => $participant->league_id,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'auction_duration' => $totalMinutes,
            'allowed_roles' => implode(',', $request->roles)
        ]);
        return back()->with('message', 'Sessione salvata!');
    }

    // --- 5. LOGICHE DI SUPPORTO ---
    private function executeBiddingWar($auction, $lastBidderId, $lastBidAmount) {
        $bestOther = Autobid::where('auction_id', $auction->id)->where('user_id', '!=', $lastBidderId)->orderBy('max_bid', 'desc')->orderBy('created_at', 'asc')->first();
        if ($bestOther) {
            if ($bestOther->max_bid > $lastBidAmount) $auction->update(['user_id' => $bestOther->user_id, 'current_bid' => $lastBidAmount + 1]);
            elseif ($bestOther->max_bid == $lastBidAmount) $auction->update(['user_id' => $bestOther->user_id, 'current_bid' => $lastBidAmount]);
            else $auction->update(['user_id' => $lastBidderId, 'current_bid' => $lastBidAmount]);
        } else {
            $auction->update(['user_id' => $lastBidderId, 'current_bid' => $lastBidAmount]);
        }
    }

    public function release(Request $request) {
        $rosterItem = Roster::with('player')->findOrFail($request->roster_id);
        $refund = ceil(($rosterItem->release_clause > 0 ? $rosterItem->release_clause : $rosterItem->purchase_price) / 2);
        $p = LeagueParticipant::where('league_id', $rosterItem->league_id)->where('user_id', $rosterItem->user_id)->first();
        if ($p) {
            $p->increment('remaining_budget', $refund);
            $p->decrement('years_budget', ($rosterItem->contract_years - floor($rosterItem->contract_years / 2)));
        }
        $rosterItem->delete();
        return back();
    }

    public function updateContract(Request $request) {
        $rosterItem = Roster::findOrFail($request->roster_id);
        $rosterItem->update(['contract_years' => $request->new_years, 'release_clause' => ($rosterItem->release_clause ?: $rosterItem->purchase_price) + $request->clausola_investment]);
        return back();
    }

    public function history() {
        $p = LeagueParticipant::where('user_id', auth()->id())->first();
        return Inertia::render('Market/History', ['league' => League::find($p->league_id), 'movements' => Roster::where('league_id', $p->league_id)->with(['player', 'user'])->orderBy('created_at', 'desc')->get()]);
    }

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