<?php

namespace App\Http\Controllers;

use App\Models\RealPlayer;
use App\Models\Roster;
use App\Models\PrimaveraRoster; 
use App\Models\Auction;
use App\Models\LineupDetail;
use App\Models\Autobid;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PlayerController extends Controller
{
    public function index()
    {
        $league = auth()->user()->leagues()->first();
        if (!$league) return redirect()->route('dashboard');

        // Prendiamo gli ID di chi è in Prima Squadra o in Primavera IN QUESTA LEGA
        $soldIds = Roster::where('league_id', $league->id)->pluck('real_player_id')->toArray();
        $primaveraIds = PrimaveraRoster::where('league_id', $league->id)->pluck('real_player_id')->toArray();

        // Uniamo le liste per avere il blocco totale
        $excludedIds = array_unique(array_merge($soldIds, $primaveraIds));

        // Mostriamo solo chi è veramente libero
        $available = RealPlayer::whereNotIn('id', $excludedIds)
            ->orderByRaw("FIELD(role, 'P', 'D', 'C', 'A')")
            ->orderBy('name', 'asc')
            ->get();

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
        $request->validate(['name' => 'required|string|max:255', 'role' => 'required|in:P,D,C,A', 'real_team' => 'required|string|max:255']); 
        RealPlayer::create(['name' => $request->name, 'role' => $request->role, 'real_team' => $request->real_team, 'initial_value' => 1, 'quotation' => 1]); 
        return back(); 
    }

    public function massUpdateQuotations(Request $request) { 
        $list = $request->input('list_to_update'); 
        if (!is_array($list)) return back(); 
        foreach ($list as $item) { 
            $player = RealPlayer::where('name', 'LIKE', trim($item['name']))->first(); 
            if ($player) $player->update(['quotation' => (int)$item['quotation']]); 
        } 
        return back(); 
    }

    public function bulkImport(Request $request) { 
        $list = $request->input('players_list'); 
        if (!is_array($list)) return back(); 
        foreach ($list as $item) { 
            RealPlayer::updateOrCreate(['name' => trim($item['name'])], ['role' => strtoupper(trim($item['role'])), 'real_team' => trim($item['team']), 'quotation' => (int)$item['quotation'], 'initial_value' => (int)$item['quotation']]); 
        } 
        return back(); 
    }

    public function destroy(RealPlayer $player) { 
        DB::transaction(function () use ($player) { 
            Roster::where('real_player_id', $player->id)->delete(); 
            PrimaveraRoster::where('real_player_id', $player->id)->delete(); 
            LineupDetail::where('real_player_id', $player->id)->delete(); 
            $auctionIds = Auction::where('real_player_id', $player->id)->pluck('id'); 
            Autobid::whereIn('auction_id', $auctionIds)->delete(); 
            Auction::where('real_player_id', $player->id)->delete(); 
            $player->delete(); 
        }); 
        return back(); 
    }
}