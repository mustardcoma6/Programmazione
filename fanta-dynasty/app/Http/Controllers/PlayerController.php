<?php

namespace App\Http\Controllers;

use App\Models\{RealPlayer, Roster, PrimaveraRoster, Auction, LineupDetail, Autobid};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PlayerController extends Controller
{
    public function index() {
        $league = auth()->user()->leagues()->first();
        if (!$league) return redirect()->route('dashboard');
        $soldIds = Roster::where('league_id', $league->id)->pluck('real_player_id');
        $primaIds = PrimaveraRoster::where('league_id', $league->id)->pluck('real_player_id');
        $excluded = $soldIds->merge($primaIds)->unique()->toArray();
        $available = RealPlayer::whereNotIn('id', $excluded)->orderByRaw("FIELD(role, 'P', 'D', 'C', 'A')")->orderBy('name', 'asc')->get();
        return Inertia::render('Players/Index', ['players' => $available]);
    }

    public function adminIndex() {
        $league = auth()->user()->leagues()->first();
        $players = RealPlayer::orderByRaw("FIELD(role, 'P', 'D', 'C', 'A')")->orderBy('name', 'asc')->get();
        if ($league) {
            foreach ($players as $p) {
                $p->owner = Roster::where('real_player_id', $p->id)->where('league_id', $league->id)->with('user')->first();
                $p->primavera_owner = PrimaveraRoster::where('real_player_id', $p->id)->where('league_id', $league->id)->with('user')->first();
            }
        }
        return Inertia::render('Admin/Players', ['players' => $players]);
    }

    public function store(Request $request) { 
        $request->validate(['name' => 'required', 'role' => 'required|in:P,D,C,A', 'real_team' => 'required']); 
        RealPlayer::create(['name' => $request->name, 'role' => $request->role, 'real_team' => $request->real_team, 'initial_value' => 1, 'quotation' => 1]); 
        return back(); 
    }

    public function bulkImport(Request $request) { 
        $list = $request->input('players_list'); 
        if (is_array($list)) {
            foreach ($list as $item) { 
                RealPlayer::updateOrCreate(['name' => trim($item['name'])], ['role' => strtoupper(trim($item['role'])), 'real_team' => trim($item['team']), 'quotation' => (int)($item['quotation'] ?? 1), 'initial_value' => (int)($item['quotation'] ?? 1)]); 
            } 
        }
        return back(); 
    }

    public function massUpdateQuotations(Request $request) { 
        $list = $request->input('list_to_update'); 
        if (is_array($list)) {
            foreach ($list as $item) { 
                $player = RealPlayer::where('name', 'LIKE', trim($item['name']))->first(); 
                if ($player) $player->update(['quotation' => (int)$item['quotation']]); 
            } 
        }
        return back(); 
    }

    // --- CANCELLAZIONE ATOMICA (METODO SENIOR) ---
    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            // 1. Eliminiamo i figli (Aste e Autobid)
            $auctionIds = DB::table('auctions')->where('real_player_id', $id)->pluck('id');
            if ($auctionIds->isNotEmpty()) {
                DB::table('autobids')->whereIn('auction_id', $auctionIds)->delete();
            }
            DB::table('auctions')->where('real_player_id', $id)->delete();

            // 2. Eliminiamo dalle squadre (Pro e Primavera)
            DB::table('rosters')->where('real_player_id', $id)->delete();
            DB::table('primavera_rosters')->where('real_player_id', $id)->delete();

            // 3. Eliminiamo dalle formazioni
            DB::table('lineup_details')->where('real_player_id', $id)->delete();

            // 4. Eliminiamo il giocatore dal listone
            DB::table('real_players')->where('id', $id)->delete();
        });

        return back()->with('message', 'Giocatore rimosso ovunque.');
    }
}