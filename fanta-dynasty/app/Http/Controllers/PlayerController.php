<?php

namespace App\Http\Controllers;

use App\Models\RealPlayer;
use App\Models\Roster;
use App\Models\Auction;
use App\Models\LineupDetail;
use App\Models\Autobid;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PlayerController extends Controller
{
    // LISTA PUBBLICA PER UTENTI
    public function index()
    {
        $league = auth()->user()->leagues()->first();
        if (!$league) return redirect()->route('dashboard');

        $soldIds = Roster::where('league_id', $league->id)->pluck('real_player_id')->toArray();
        $available = RealPlayer::whereNotIn('id', $soldIds)->orderBy('role', 'desc')->orderBy('name', 'asc')->get();

        return Inertia::render('Players/Index', ['players' => $available]);
    }

    // LISTA PER ADMIN
    public function adminIndex()
    {
        $players = RealPlayer::orderBy('role', 'desc')->orderBy('name', 'asc')->get();
        return Inertia::render('Admin/Players', ['players' => $players]);
    }

    // SALVA SINGOLO GIOCATORE
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|in:P,D,C,A',
            'real_team' => 'required|string|max:255',
        ]);

        RealPlayer::create([
            'name' => $request->name,
            'role' => $request->role,
            'real_team' => $request->real_team,
            'initial_value' => 1,
            'quotation' => 1
        ]);

        return back();
    }

    // AGGIORNAMENTO MASSIVO QUOTAZIONI
    public function massUpdateQuotations(Request $request)
    {
        $data = $request->input('data');
        if (!is_array($data)) return back();

        foreach ($data as $item) {
            $cleanName = trim($item['name']);
            $player = RealPlayer::where('name', 'LIKE', $cleanName)->first();
            if ($player) {
                $player->update(['quotation' => (int)$item['quotation']]);
            }
        }
        return back();
    }

    // ELIMINA GIOCATORE
    public function destroy(RealPlayer $player)
    {
        DB::transaction(function () use ($player) {
            Roster::where('real_player_id', $player->id)->delete();
            LineupDetail::where('real_player_id', $player->id)->delete();
            $auctionIds = Auction::where('real_player_id', $player->id)->pluck('id');
            Autobid::whereIn('auction_id', $auctionIds)->delete();
            Auction::where('real_player_id', $player->id)->delete();
            $player->delete();
        });

        return back();
    }
}