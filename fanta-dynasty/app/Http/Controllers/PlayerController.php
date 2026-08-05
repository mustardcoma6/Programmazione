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
    // LISTA PUBBLICA SVINCOLATI (Per gli utenti)
    public function index()
    {
        $league = auth()->user()->leagues()->first();
        if (!$league) return redirect()->route('dashboard');

        $soldIds = Roster::where('league_id', $league->id)->pluck('real_player_id')->toArray();
        $available = RealPlayer::whereNotIn('id', $soldIds)->orderBy('role', 'desc')->orderBy('name', 'asc')->get();

        return Inertia::render('Players/Index', ['players' => $available]);
    }

    // GESTIONE ADMIN: Mostra TUTTI i giocatori (Liberi e Occupati)
    public function adminIndex()
    {
        $league = auth()->user()->leagues()->first();
        
        // Prendiamo tutti i giocatori
        $players = RealPlayer::orderBy('role', 'desc')->orderBy('name', 'asc')->get();
        
        // Per ogni giocatore, controlliamo se è occupato in questa lega per avvisare l'admin
        foreach ($players as $p) {
            $p->owner = Roster::where('real_player_id', $p->id)
                ->where('league_id', $league->id)
                ->with('user')
                ->first();
        }

        return Inertia::render('Admin/Players', ['players' => $players]);
    }

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
            'initial_value' => 1
        ]);

        return back()->with('message', 'Giocatore aggiunto al sistema!');
    }

    // ELIMINAZIONE TOTALE (POTERE ADMIN)
    public function destroy(RealPlayer $player)
    {
        DB::transaction(function () use ($player) {
            // 1. Lo rimuoviamo dalle rose di tutte le squadre (Roster)
            Roster::where('real_player_id', $player->id)->delete();

            // 2. Lo rimuoviamo dalle formazioni (Lineup)
            LineupDetail::where('real_player_id', $player->id)->delete();

            // 3. Lo rimuoviamo dalle aste attive
            $auctionIds = Auction::where('real_player_id', $player->id)->pluck('id');
            Autobid::whereIn('auction_id', $auctionIds)->delete();
            Auction::where('real_player_id', $player->id)->delete();

            // 4. Infine lo eliminiamo dal listone mondiale
            $player->delete();
        });

        return back()->with('message', 'Giocatore eliminato ovunque con successo.');
    }
    public function massUpdateQuotations(Request $request)
    {
        $request->validate(['data' => 'required|array']);

        foreach ($request->data as $item) {
            // Cerchiamo il giocatore per nome e aggiorniamo la quotazione
            $player = RealPlayer::where('name', $item['name'])->first();
            if ($player) {
                $player->update(['quotation' => $item['quotation']]);
            }
        }

        return back()->with('message', 'Quotazioni aggiornate!');
    }
}