<?php

namespace App\Http\Controllers;

// CONTROLLA CHE CI SIANO TUTTI QUESTI:
use App\Models\League;
use App\Models\LeagueParticipant;
use App\Models\Roster;
use App\Models\PrimaveraRoster; // <--- QUESTO È VITALI
use App\Models\RealPlayer;
use App\Models\User;
use App\Models\MarketSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class LeagueController extends Controller
{
    // --- GESTIONE PRIMAVERA (ADMIN) ---
    public function managePrimavera()
    {
        $user = auth()->user();
        $league = League::where('admin_id', $user->id)->first();
        if (!$league) return redirect()->route('dashboard');

        $teams = LeagueParticipant::where('league_id', $league->id)->with('user')->get();
        foreach ($teams as $team) {
            $team->primavera_players = PrimaveraRoster::where('league_id', $league->id)->where('user_id', $team->user_id)->with('player')->get();
        }

        $soldIds = Roster::where('league_id', $league->id)->pluck('real_player_id')->toArray();
        $primaveraIds = PrimaveraRoster::where('league_id', $league->id)->pluck('real_player_id')->toArray();
        $availablePlayers = RealPlayer::whereNotIn('id', array_merge($soldIds, $primaveraIds))->orderBy('role', 'desc')->get();

        return Inertia::render('Admin/Primavera', [
            'league' => $league,
            'teams' => $teams,
            'availablePlayers' => $availablePlayers
        ]);
    }

    public function assignPrimavera(Request $request)
    {
        $request->validate(['player_id' => 'required', 'user_id' => 'required', 'price' => 'required|integer']);
        $league = auth()->user()->leagues()->first();

        PrimaveraRoster::create([
            'league_id' => $league->id,
            'user_id' => $request->user_id,
            'real_player_id' => $request->player_id,
            'purchase_price' => $request->price
        ]);

        // REGOLA: Scaliamo dal budget PRIMA ROSA
        $p = LeagueParticipant::where('league_id', $league->id)->where('user_id', $request->user_id)->first();
        $p->decrement('remaining_budget', $request->price);

        return back();
    }

    public function removePrimavera(Request $request)
    {
        $item = PrimaveraRoster::findOrFail($request->roster_id);
        $p = LeagueParticipant::where('league_id', $item->league_id)->where('user_id', $item->user_id)->first();
        $p->increment('remaining_budget', $item->purchase_price);
        $item->delete();
        return back();
    }

    // --- AGGIORNAMENTO BUDGET (CREDITI PRIMA ROSA + PRIMAVERA) ---
    public function updateResources(Request $request)
    {
        $p = LeagueParticipant::findOrFail($request->participant_id);
        $p->update([
            'remaining_budget' => $request->new_credits,
            'years_budget' => $request->new_years,
            'remaining_primavera_budget' => $request->new_primavera_credits // AGGIUNTO
        ]);
        return back();
    }

    // [Funzioni standard mantenute...]
    public function manageCredits() { $user = auth()->user(); $league = League::where('admin_id', $user->id)->first(); $teams = LeagueParticipant::where('league_id', $league->id)->with('user')->get(); return Inertia::render('Admin/Credits', ['league' => $league, 'teams' => $teams]); }
    public function manageRosters() { $user = auth()->user(); $league = League::where('admin_id', $user->id)->first(); $teams = LeagueParticipant::where('league_id', $league->id)->with('user')->get(); foreach ($teams as $team) { $team->players = Roster::where('league_id', $league->id)->where('user_id', $team->user_id)->with('player')->get(); } $soldIds = Roster::where('league_id', $league->id)->pluck('real_player_id')->toArray(); $primaveraIds = PrimaveraRoster::where('league_id', $league->id)->pluck('real_player_id')->toArray(); $availablePlayers = RealPlayer::whereNotIn('id', array_merge($soldIds, $primaveraIds))->orderBy('role', 'desc')->get(); return Inertia::render('Admin/Rosters', ['league' => $league, 'teams' => $teams, 'availablePlayers' => $availablePlayers]); }
    public function assignPlayer(Request $request) { $league = auth()->user()->leagues()->first(); Roster::create(['league_id' => $league->id, 'user_id' => $request->user_id, 'real_player_id' => $request->player_id, 'purchase_price' => $request->price, 'contract_years' => $request->years]); $p = LeagueParticipant::where('league_id', $league->id)->where('user_id', $request->user_id)->first(); $p->decrement('remaining_budget', $request->price); return back(); }
    public function removePlayer(Request $request) { $rosterItem = Roster::findOrFail($request->roster_id); $p = LeagueParticipant::where('league_id', $rosterItem->league_id)->where('user_id', $rosterItem->user_id)->first(); $p->increment('remaining_budget', $rosterItem->purchase_price); $rosterItem->delete(); return back(); }
    public function societaIndex() { $user = auth()->user(); $league = $user->leagues()->first(); $participants = LeagueParticipant::where('league_id', $league->id)->with('user')->get(); foreach ($participants as $p) { $p->years_used = Roster::where('league_id', $league->id)->where('user_id', $p->user_id)->sum('contract_years'); } return Inertia::render('Societa/Index', ['league' => $league, 'participants' => $participants]); }
    public function storeSession(Request $request) { $p = LeagueParticipant::where('user_id', auth()->id())->first(); $timeParts = explode(':', $request->auction_time); $totalMinutes = ($timeParts[0] * 60) + $timeParts[1]; MarketSession::create(['league_id' => $p->league_id, 'start_at' => $request->start_at, 'end_at' => $request->end_at, 'auction_duration' => $totalMinutes, 'allowed_roles' => implode(',', $request->roles)]); return back(); }
}