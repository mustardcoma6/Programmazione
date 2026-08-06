<?php

namespace App\Http\Controllers;

use App\Models\{RealPlayer, Roster, PrimaveraRoster, Auction, LineupDetail, Autobid};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PlayerController extends Controller
{
    public function index()
    {
        $league = auth()->user()->leagues()->first();
        if (!$league) return redirect()->route('dashboard');

        // Prendiamo TUTTI gli ID occupati (Pro + Primavera)
        $soldIds = Roster::where('league_id', $league->id)->pluck('real_player_id');
        $primaveraIds = PrimaveraRoster::where('league_id', $league->id)->pluck('real_player_id');
        
        $excluded = $soldIds->merge($primaveraIds)->unique()->toArray();

        // Lista pulita senza doppioni
        $available = RealPlayer::whereNotIn('id', $excluded)
            ->orderByRaw("FIELD(role, 'P', 'D', 'C', 'A')")
            ->orderBy('name', 'asc')
            ->get();

        return Inertia::render('Players/Index', ['players' => $available]);
    }

    public function adminIndex() {
        $players = RealPlayer::orderByRaw("FIELD(role, 'P', 'D', 'C', 'A')")->orderBy('name', 'asc')->get();
        $league = auth()->user()->leagues()->first();
        if ($league) {
            foreach ($players as $p) {
                $p->owner = Roster::where('real_player_id', $p->id)->where('league_id', $league->id)->with('user')->first();
                $p->primavera_owner = PrimaveraRoster::where('real_player_id', $p->id)->where('league_id', $league->id)->with('user')->first();
            }
        }
        return Inertia::render('Admin/Players', ['players' => $players]);
    }

    public function store(Request $request) { 
        $request->validate(['name' => 'required', 'role' => 'required', 'real_team' => 'required']);
        RealPlayer::create(['name' => $request->name, 'role' => $request->role, 'real_team' => $request->real_team, 'initial_value' => 1, 'quotation' => 1]);
        return back();
    }

    public function bulkImport(Request $request) {
        $list = $request->input('players_list');
        if (is_array($list)) {
            foreach ($list as $item) {
                RealPlayer::updateOrCreate(['name' => trim($item['name'])], [
                    'role' => strtoupper(trim($item['role'])),
                    'real_team' => trim($item['team']),
                    'quotation' => (int)$item['quotation'],
                    'initial_value' => (int)$item['quotation']
                ]);
            }
        }
        return back();
    }

    public function destroy(RealPlayer $player) {
        DB::transaction(function () use ($player) {
            Roster::where('real_player_id', $player->id)->delete();
            PrimaveraRoster::where('real_player_id', $player->id)->delete();
            LineupDetail::where('real_player_id', $player->id)->delete();
            Auction::where('real_player_id', $player->id)->delete();
            $player->delete();
        });
        return back();
    }
}