<?php

namespace App\Http\Controllers;

use App\Models\{RealPlayer, LeagueParticipant, Roster, PrimaveraRoster, League, Auction, MarketSession, Autobid, MarketValueHistory};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarketController extends Controller
{
    public function sessions() 
    {
        $user = auth()->user();
        $p = LeagueParticipant::where('user_id', $user->id)->first();
        if (!$p) return redirect()->route('dashboard');
        $league = League::find($p->league_id);
        $sessions = MarketSession::where('league_id', $league->id)->orderBy('start_at', 'desc')->get();
        return Inertia::render('Market/Sessions', ['league' => $league, 'sessions' => $sessions]);
    }

    public function storeSession(Request $request) 
    {
        $p = LeagueParticipant::where('user_id', auth()->id())->first();
        $request->validate(['start_at' => 'required|date', 'end_at' => 'required|date|after:start_at', 'auction_time' => 'required', 'roles' => 'required|array']);
        $inputTime = $request->auction_time;
        $minutes = str_contains($inputTime, ':') ? ((int)explode(':', $inputTime)[0] * 60) + (int)explode(':', $inputTime)[1] : (int)$inputTime;
        if ($minutes <= 0) $minutes = 5;
        MarketSession::create(['league_id' => $p->league_id, 'start_at' => $request->start_at, 'end_at' => $request->end_at, 'auction_duration' => $minutes, 'allowed_roles' => implode(',', $request->roles)]);
        return back()->with('message', 'Sessione salvata!');
    }

    public function closeMarketNow() 
    {
        $p = LeagueParticipant::where('user_id', auth()->id())->first();
        if ($p) {
            MarketSession::where('league_id', $p->league_id)->where('end_at', '>', now())->update(['end_at' => now()]);
            Auction::where('league_id', $p->league_id)->where('is_finished', false)->delete();
        }
        return back()->with('message', 'Aste annullate.');
    }

    public function auctions() 
    {
        $u = auth()->user();
        $p = LeagueParticipant::where('user_id', $u->id)->first();
        if (!$p) return redirect()->route('dashboard');
        $l = League::find($p->league_id);
        $this->processExpiredAuctions($l->id);
        $now = Carbon::now('Europe/Rome');
        $curr = MarketSession::where('league_id', $l->id)->where('start_at', '<=', $now)->where('end_at', '>=', $now)->first();
        $takenPlayers = array_merge(Roster::where('league_id', $l->id)->pluck('real_player_id')->toArray(), PrimaveraRoster::where('league_id', $l->id)->pluck('real_player_id')->toArray());

        return Inertia::render('Market/Auctions', [
            'league' => $l, 'isMarketOpen' => (bool)$curr, 'currentSession' => $curr, 'myData' => $p, 
            'frozenCredits' => (int)(Auction::where('league_id', $l->id)->where('user_id', $u->id)->where('is_finished', false)->sum('current_bid') ?? 0), 
            'myRoster' => Roster::where('league_id', $l->id)->where('user_id', $u->id)->with('player')->get(), 
            'availablePlayers' => RealPlayer::whereNotIn('id', $takenPlayers)->orderBy('role', 'desc')->get(), 
            'activeAuctions' => Auction::where('league_id', $l->id)->where('is_finished', false)->with(['player', 'user'])->get()
        ]);
    }
    
    // --- LOGICA RILANCIO AUTOMATICO PROFESSIONALE ---
    public function buy(Request $request) 
    {
        return DB::transaction(function () use ($request) {
            $l = League::findOrFail($request->league_id);
            $now = Carbon::now('Europe/Rome'); 
            $s = MarketSession::where('league_id', $l->id)->where('start_at', '<=', $now)->where('end_at', '>=', $now)->first();
            
            if (!$s) return back()->withErrors(['error' => 'Il mercato è chiuso.']);

            $maxBidUtente = (int)$request->price;
            $user = auth()->user();

            // Blocco l'asta per evitare modifiche contemporanee
            $a = Auction::where('league_id', $l->id)
                ->where('real_player_id', $request->player_id)
                ->where('is_finished', false)
                ->lockForUpdate()
                ->first();

            // Controllo Budget
            $p = LeagueParticipant::where('league_id', $l->id)->where('user_id', $user->id)->first();
            if ($p->remaining_budget < $maxBidUtente) {
                return back()->withErrors(['error' => 'Budget insufficiente per questa offerta massima.']);
            }

            if (!$a) {
                // Nuova Asta
                $a = Auction::create([
                    'league_id' => $l->id, 'real_player_id' => $request->player_id, 'user_id' => $user->id,
                    'current_bid' => 1, 'expires_at' => $now->copy()->addMinutes($s->auction_duration), 'is_finished' => false
                ]);
            } else {
                if ($maxBidUtente <= $a->current_bid) {
                    return back()->withErrors(['error' => 'L\'offerta deve essere superiore al prezzo attuale di ' . $a->current_bid]);
                }
            }

            // Salvo o aggiorno il limite dell'utente
            Autobid::updateOrCreate(['auction_id' => $a->id, 'user_id' => $user->id], ['max_bid' => $maxBidUtente]);

            // GUERRA DI RILANCI
            $this->executeBiddingWar($a);

            // Estensione 1 minuto se mancano meno di 30 sec
            if ($now->diffInSeconds($a->expires_at, false) <= 30) {
                $a->update(['expires_at' => $now->copy()->addMinute()]);
            }

            return back();
        });
    }

    private function executeBiddingWar($a) 
    {
        // Prendo i due massimi più alti
        $bids = Autobid::where('auction_id', $a->id)
            ->orderBy('max_bid', 'desc')
            ->orderBy('created_at', 'asc') // A parità di max_bid, vince il primo che ha puntato
            ->limit(2)
            ->get();

        $vincitore = $bids[0];
        $secondo = $bids[1] ?? null;

        if (!$secondo) {
            // Unico offerente: il prezzo rimane 1 (o il prezzo di chiamata)
            $a->update(['user_id' => $vincitore->user_id, 'current_bid' => max(1, $a->current_bid)]);
        } else {
            if ($vincitore->max_bid > $secondo->max_bid) {
                // Vince il primo per distacco: prezzo = secondo + 1
                $a->update(['user_id' => $vincitore->user_id, 'current_bid' => $secondo->max_bid + 1]);
            } else {
                // Parità perfetta: vince il primo che ha messo la cifra (vincitore per 'created_at')
                // Il prezzo va al massimo comune
                $a->update(['user_id' => $vincitore->user_id, 'current_bid' => $vincitore->max_bid]);
            }
        }
    }
    
    // --- RESTANTI FUNZIONI INVARIATE ---
    public function release(Request $request) { $r = Roster::with('player')->findOrFail($request->roster_id); $p = LeagueParticipant::where('user_id', $r->user_id)->first(); if ($p) { $p->increment('remaining_budget', ceil(($r->release_clause ?: $r->purchase_price) / 2)); $p->decrement('years_budget', ($r->contract_years - floor($r->contract_years / 2))); } $r->delete(); return back(); }
    public function updateContract(Request $request) { $r = Roster::findOrFail($request->roster_id); $r->update(['contract_years' => $request->new_years, 'release_clause' => ($r->release_clause ?: $r->purchase_price) + $request->clausola_investment]); return back(); }
    public function history() { $p = LeagueParticipant::where('user_id', auth()->id())->first(); return Inertia::render('Market/History', ['league' => League::find($p->league_id), 'movements' => Roster::where('league_id', $p->league_id)->with(['player', 'user'])->orderBy('created_at', 'desc')->get()]); }
    public function financesPage() {
        $user = auth()->user(); $leagues = $user->leagues()->get(); $firstLeague = $leagues->first(); $lp = LeagueParticipant::where('user_id', $user->id)->where('league_id', $firstLeague->id)->first();
        if (!$lp || $lp->games_played == 0) return Inertia::render('Societa/Finanze', ['stats' => null, 'message' => 'Gioca almeno una partita.']);
        $inv = 130; $plus = 440; $topP = RealPlayer::where('role', 'P')->orderBy('quotation', 'desc')->limit(3)->get()->sum('quotation'); $topD = RealPlayer::where('role', 'D')->orderBy('quotation', 'desc')->limit(8)->get()->sum('quotation'); $topC = RealPlayer::where('role', 'C')->orderBy('quotation', 'desc')->limit(8)->get()->sum('quotation'); $topA = RealPlayer::where('role', 'A')->orderBy('quotation', 'desc')->limit(6)->get()->sum('quotation'); $benchmarkVal = ($topP + $topD + $topC + $topA) ?: 1; $calcolaGol = function($p) { if ($p < 66) return 0; if ($p < 70) return 1; return 2 + floor(($p - 70) / 5); };
        $tutti = LeagueParticipant::where('league_id', $firstLeague->id)->get(); $maxGolLega = $tutti->map(fn($s) => $calcolaGol($s->games_played > 0 ? ($s->total_points / $s->games_played) : 0))->max() ?: 1;
        $allTeamsValues = [];
        foreach ($tutti as $s) {
            $rSum = Roster::where('user_id', $s->user_id)->where('league_id', $s->league_id)->with('player')->get()->sum(fn($r) => $r->player->quotation ?? 0);
            $val = $inv + ($plus * ($rSum / $benchmarkVal) * ($calcolaGol($s->games_played > 0 ? ($s->total_points / $s->games_played) : 0) / $maxGolLega));
            $prev = MarketValueHistory::where('user_id', $s->user_id)->where('matchday', '<', $s->games_played)->orderBy('matchday', 'desc')->first();
            $trend = ['dir' => 'stable', 'perc' => 0]; if ($prev && $prev->value > 0) { $diff = $val - $prev->value; $trend = ['dir' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'stable'), 'perc' => round(abs(($diff / $prev->value) * 100), 1)]; }
            $allTeamsValues[] = ['user_id' => $s->user_id, 'team_name' => $s->team_name, 'valore' => round($val, 2), 'trend' => $trend];
        }
        usort($allTeamsValues, fn($a, $b) => $b['valore'] <=> $a['valore']);
        $myFin = collect($allTeamsValues)->firstWhere('user_id', $user->id);
        return Inertia::render('Societa/Finanze', [
            'leagues' => $leagues, 'myData' => $lp, 'allTeams' => $allTeamsValues, 'history' => MarketValueHistory::where('user_id', $user->id)->orderBy('matchday', 'asc')->get(),
            'stats' => ['valore_monetario' => $myFin['valore'], 'trend' => $myFin['trend'], 'asset_quality_perc' => round((Roster::where('user_id', $user->id)->where('league_id', $firstLeague->id)->with('player')->get()->sum(fn($r) => $r->player->quotation ?? 0) / $benchmarkVal) * 100, 1), 'winning_efficiency_perc' => round(($calcolaGol($lp->total_points / $lp->games_played) / $maxGolLega) * 100, 1), 'tuoi_gol' => $calcolaGol($lp->total_points / $lp->games_played), 'leader_gol' => $maxGolLega, 'benchmark_val' => $benchmarkVal]
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