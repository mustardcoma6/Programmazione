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
        $request->validate(['name' => 'required|string', 'role' => 'required|in:P,D,C,A', 'real_team' => 'required', 'nationality' => 'required']); 
        RealPlayer::create(['name' => $request->name, 'role' => $request->role, 'real_team' => $request->real_team, 'nationality' => $request->nationality, 'initial_value' => 1, 'quotation' => 1]); 
        return back(); 
    }

    // --- IMPORTATORE BULK CORAZZATO ---
    public function bulkImport(Request $request) { 
        $list = $request->input('players_list'); 
        if (!is_array($list)) return back(); 

        foreach ($list as $item) { 
            // 1. Pulizia e Validazione Dati
            $name = isset($item['name']) ? trim($item['name']) : null;
            $role = isset($item['role']) ? strtoupper(trim($item['role'])) : null;
            $team = isset($item['team']) ? trim($item['team']) : 'Sconosciuta';
            $quote = isset($item['quotation']) ? (int)$item['quotation'] : 1;
            $nation = isset($item['nationality']) ? trim($item['nationality']) : 'Italia';

            // 2. Se mancano Nome o Ruolo, o se il Ruolo è invalido, SALTA la riga invece di crashare
            if (!$name || !in_array($role, ['P', 'D', 'C', 'A'])) {
                continue; 
            }

            // 3. Logica Upsert
            RealPlayer::updateOrCreate(
                ['name' => $name], 
                [
                    'role' => $role, 
                    'real_team' => $team, 
                    'nationality' => $nation,
                    'quotation' => $quote, 
                    'initial_value' => $quote
                ]
            ); 
        } 
        return back()->with('message', 'Sincronizzazione completata!'); 
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

    public function destroy(RealPlayer $player) { 
        DB::transaction(function () use ($player) { 
            Roster::where('real_player_id', $player->id)->delete(); 
            PrimaveraRoster::where('real_player_id', $player->id)->delete(); 
            LineupDetail::where('real_player_id', $player->id)->delete(); 
            $auctionIds = Auction::where('real_player_id', $player->id)->pluck('id'); 
            if($auctionIds->count() > 0) Autobid::whereIn('auction_id', $auctionIds)->delete(); 
            Auction::where('real_player_id', $player->id)->delete(); 
            $player->delete(); 
        }); 
        return back(); 
    }
}