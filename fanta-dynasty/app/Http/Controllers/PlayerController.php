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
        $primaveraIds = PrimaveraRoster::where('league_id', $league->id)->pluck('real_player_id');
        $excluded = $soldIds->merge($primaveraIds)->unique()->toArray();
        $available = RealPlayer::whereNotIn('id', $excluded)->orderByRaw("FIELD(role, 'P', 'D', 'C', 'A')")->orderBy('name', 'asc')->get();
        return Inertia::render('Players/Index', ['players' => $available]);
    }

    public function adminIndex() {
        $players = RealPlayer::orderByRaw("FIELD(role, 'P', 'D', 'C', 'A')")->orderBy('name', 'asc')->get();
        return Inertia::render('Admin/Players', ['players' => $players]);
    }

    public function store(Request $request) { 
        $request->validate([
            'name' => 'required|string|max:255', 
            'role' => 'required|in:P,D,C,A', 
            'real_team' => 'required|string|max:255',
            'nationality' => 'required|string|max:255' // AGGIUNTO
        ]); 
        RealPlayer::create([
            'name' => $request->name, 
            'role' => $request->role, 
            'real_team' => $request->real_team, 
            'nationality' => $request->nationality, // AGGIUNTO
            'initial_value' => 1, 
            'quotation' => 1
        ]); 
        return back(); 
    }

    public function bulkImport(Request $request) { 
        $list = $request->input('players_list'); 
        if (!is_array($list)) return back(); 
        foreach ($list as $item) { 
            RealPlayer::updateOrCreate(['name' => trim($item['name'])], [
                'role' => strtoupper(trim($item['role'])), 
                'real_team' => trim($item['team']), 
                'nationality' => trim($item['nationality'] ?? 'Italia'), // AGGIUNTO
                'quotation' => (int)$item['quotation'], 
                'initial_value' => (int)$item['quotation']
            ]); 
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