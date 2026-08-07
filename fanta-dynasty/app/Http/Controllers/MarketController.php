<?php

namespace App\Http\Controllers;

use App\Models\{RealPlayer, LeagueParticipant, Roster, PrimaveraRoster, League, Auction, MarketSession, Autobid};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

<?php

namespace App\Http\Controllers;

use App\Models\{RealPlayer, LeagueParticipant, Roster, PrimaveraRoster, League, Auction, MarketSession, Autobid};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarketController extends Controller
{
    // --- PAGINA FINANZE SOCIETARIE (AGGIORNATA CON RANKING QUOTAZIONI) ---
    public function financesPage() {
        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();
        if (!$participant) return redirect()->route('dashboard');

        $league = League::find($participant->league_id);
        
        // 1. Prendiamo tutti i partecipanti della lega
        $allTeams = LeagueParticipant::where('league_id', $league->id)
            ->with('user')
            ->get();

        // 2. Per ogni squadra calcoliamo il valore totale delle quotazioni (Pro + Primavera)
        foreach ($allTeams as $team) {
            // Somma quotazioni Prima Squadra
            $proValue = DB::table('rosters')
                ->join('real_players', 'rosters.real_player_id', '=', 'real_players.id')
                ->where('rosters.user_id', $team->user_id)
                ->where('rosters.league_id', $league->id)
                ->sum('real_players.quotation');

            // Somma quotazioni Primavera
            $primaveraValue = DB::table('primavera_rosters')
                ->join('real_players', 'primavera_rosters.real_player_id', '=', 'real_players.id')
                ->where('primavera_rosters.user_id', $team->user_id)
                ->where('primavera_rosters.league_id', $league->id)
                ->sum('real_players.quotation');

            $team->total_quotation_value = (int)$proValue + (int)$primaveraValue;
        }

        // 3. Ordiniamo le squadre dal valore più alto al più basso
        $sortedTeams = $allTeams->sortByDesc('total_quotation_value')->values()->all();

        return Inertia::render('Societa/Finances', [
            'myData' => $participant,
            'ranking' => $sortedTeams
        ]);
    }

    public function buy(Request $request) 
    {
        $request->validate(['player_id' => 'required', 'price' => 'nullable|integer']);
        $user = auth()->user(); $now = Carbon::now('Europe/Rome');
        $participant = LeagueParticipant::where('user_id', $user->id)->first();
        
        $session = MarketSession::where('league_id', $participant->league_id)->where('start_at', '<=', $now)->where('end_at', '>=', $now)->first();
        if (!$session) return back()->withErrors(['error' => 'Il mercato è chiuso!']);

        $auction = Auction::where('league_id', $participant->league_id)->where('real_player_id', $request->player_id)->where('is_finished', false)->first();

        if (!$auction) {
            if (now()->diffInMinutes($session->end_at, false) < 90) return back()->withErrors(['error' => 'Chiamate bloccate (manca meno di 1h 30m)']);
            $auction = Auction::create(['league_id' => $participant->league_id, 'real_player_id' => $request->player_id, 'user_id' => $user->id, 'current_bid' => 0, 'expires_at' => now()->copy()->addMinutes($session->auction_duration), 'is_finished' => false]);
        }

        if ($request->max_autobid) Autobid::updateOrCreate(['auction_id' => $auction->id, 'user_id' => $user->id], ['max_bid' => $request->max_autobid]);
        
        $newBid = $request->price ?? ($auction->current_bid + 1);
        $this->executeBiddingWar($auction, $user->id, $newBid);

        if ($now->diffInSeconds($auction->expires_at, false) <= 30) $auction->update(['expires_at' => $now->copy()->addMinute()]);
        
        return back();
    }

    private function executeBiddingWar($auction, $lastBidderId, $lastBidAmount) 
    {
        $bestOther = Autobid::where('auction_id', $auction->id)->where('user_id', '!=', $lastBidderId)->orderBy('max_bid', 'desc')->orderBy('created_at', 'asc')->first();
        if ($bestOther) {
            if ($bestOther->max_bid > $lastBidAmount) $auction->update(['user_id' => $bestOther->user_id, 'current_bid' => $lastBidAmount + 1]);
            elseif ($bestOther->max_bid == $lastBidAmount) $auction->update(['user_id' => $bestOther->user_id, 'current_bid' => $lastBidAmount]);
            else $auction->update(['user_id' => $lastBidderId, 'current_bid' => $lastBidAmount]);
        } else { $auction->update(['user_id' => $lastBidderId, 'current_bid' => $lastBidAmount]); }
    }

    public function release(Request $request) 
    {
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

    public function updateContract(Request $request) 
    {
        $rosterItem = Roster::findOrFail($request->roster_id);
        $rosterItem->update(['contract_years' => $request->new_years, 'release_clause' => ($rosterItem->release_clause ?: $rosterItem->purchase_price) + $request->clausola_investment]);
        return back();
    }

    public function history() 
    {
        $p = LeagueParticipant::where('user_id', auth()->id())->first();
        if (!$p) return redirect()->route('dashboard');
        return Inertia::render('Market/History', ['league' => League::find($p->league_id), 'movements' => Roster::where('league_id', $p->league_id)->with(['player', 'user'])->orderBy('created_at', 'desc')->get()]);
    }

    public function financesPage() 
    {
        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();
        if (!$participant) return redirect()->route('dashboard');
        return Inertia::render('Societa/Finances', ['myData' => $participant]);
    }

    public function primaveraPage() 
    {
        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();
        if (!$participant) return redirect()->route('dashboard');
        $players = PrimaveraRoster::where('league_id', $participant->league_id)->where('user_id', $user->id)->with('player')->get();
        return Inertia::render('Societa/Primavera', ['myData' => $participant, 'primaveraPlayers' => $players]);
    }

    public function sessions() 
    {
        $p = LeagueParticipant::where('user_id', auth()->id())->first();
        if (!$p) return redirect()->route('dashboard');
        return Inertia::render('Market/Sessions', ['league' => League::find($p->league_id), 'sessions' => MarketSession::where('league_id', $p->league_id)->orderBy('start_at', 'desc')->get()]);
    }

    public function storeSession(Request $request) 
    {
        $p = LeagueParticipant::where('user_id', auth()->id())->first();
        $timeParts = explode(':', $request->auction_time);
        $totalMinutes = ($timeParts[0] * 60) + $timeParts[1];
        MarketSession::create(['league_id' => $p->league_id, 'start_at' => $request->start_at, 'end_at' => $request->end_at, 'auction_duration' => $totalMinutes, 'allowed_roles' => implode(',', $request->roles)]);
        return back();
    }

    public function closeMarketNow(League $league) 
    {
        MarketSession::where('league_id', $league->id)->where('end_at', '>', now())->update(['end_at' => now()]);
        Auction::where('league_id', $league->id)->where('is_finished', false)->delete();
        return redirect()->route('market.auctions');
    }

    private function processExpiredAuctions($leagueId) 
    {
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