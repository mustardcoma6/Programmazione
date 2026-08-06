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
    /**
     * LISTA PUBBLICA PER UTENTI
     * Mostra solo i calciatori che non appartengono a nessuna squadra della lega attuale.
     */
    public function index()
    {
        $league = auth()->user()->leagues()->first();
        if (!$league) return redirect()->route('dashboard');

        // 1. Prendiamo gli ID dei venduti in Prima Squadra
        $soldIds = Roster::where('league_id', $league->id)->pluck('real_player_id')->toArray();
        
        // 2. Prendiamo gli ID dei venduti in Primavera (IL PEZZO MANCANTE)
        $primaveraIds = PrimaveraRoster::where('league_id', $league->id)->pluck('real_player_id')->toArray();

        // Uniamo le liste degli occupati
        $excludedIds = array_merge($soldIds, $primaveraIds);

        // Mostriamo solo chi NON è in nessuna delle due liste
        $available = RealPlayer::whereNotIn('id', $excludedIds)
            ->orderByRaw("FIELD(role, 'P', 'D', 'C', 'A')")
            ->orderBy('name', 'asc')
            ->get();

        return Inertia::render('Players/Index', ['players' => $available]);
    }

    /**
     * LISTA PER ADMIN (GESTIONE)
     * Mostra tutti i calciatori e indica se sono già in una squadra.
     */
    public function adminIndex()
    {
        $league = auth()->user()->leagues()->first();
        $players = RealPlayer::orderByRaw("FIELD(role, 'P', 'D', 'C', 'A')")
            ->orderBy('name', 'asc')
            ->get();

        // Aggiungiamo l'informazione sul proprietario per ogni giocatore
        if ($league) {
            foreach ($players as $p) {
                $p->owner = Roster::where('real_player_id', $p->id)
                    ->where('league_id', $league->id)
                    ->with('user')
                    ->first();
            }
        }

        return Inertia::render('Admin/Players', ['players' => $players]);
    }

    /**
     * SALVA SINGOLO CALCIATORE
     */
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

        return back()->with('message', 'Calciatore creato!');
    }

    /**
     * AGGIORNAMENTO MASSIVO QUOTAZIONI
     * Usato per aggiornare solo i prezzi da una lista Nome,Quotazione.
     */
    public function massUpdateQuotations(Request $request)
    {
        $inputList = $request->input('list_to_update');

        if (!is_array($inputList)) {
            return back()->withErrors(['error' => 'Dati non validi.']);
        }

        foreach ($inputList as $item) {
            $cleanName = trim($item['name']);
            $player = RealPlayer::where('name', 'LIKE', $cleanName)->first();
           
            if ($player) {
                $player->update([
                    'quotation' => (int)$item['quotation']
                ]);
            }
        }

        return back()->with('message', 'Quotazioni aggiornate!');
    }

    /**
     * IMPORTATORE MASTER (UPSERT)
     * Crea i nuovi o aggiorna gli esistenti (Nome, Ruolo, Squadra, Quotazione).
     */
    public function bulkImport(Request $request)
    {
        $list = $request->input('players_list');

        if (!is_array($list)) {
            return back()->withErrors(['error' => 'Dati non validi.']);
        }

        foreach ($list as $item) {
            $name = trim($item['name']);
            $role = strtoupper(trim($item['role']));
            $team = trim($item['team']);
            $quotation = (int)$item['quotation'];

            // Logica Upsert: cerca per nome, aggiorna o crea.
            RealPlayer::updateOrCreate(
                ['name' => $name],
                [
                    'role' => $role,
                    'real_team' => $team,
                    'quotation' => $quotation,
                    'initial_value' => $quotation 
                ]
            );
        }

        return back()->with('message', 'Listone sincronizzato con successo!');
    }

    /**
     * ELIMINAZIONE CALCIATORE (CASCADE)
     */
    public function destroy(RealPlayer $player)
    {
        DB::transaction(function () use ($player) {
            // Rimuoviamo il giocatore da tutte le rose
            Roster::where('real_player_id', $player->id)->delete();
            
            // Rimuoviamo dalle formazioni
            LineupDetail::where('real_player_id', $player->id)->delete();
            
            // Puliamo le aste e i rilanci automatici collegati
            $auctionIds = Auction::where('real_player_id', $player->id)->pluck('id');
            Autobid::whereIn('auction_id', $auctionIds)->delete();
            Auction::where('real_player_id', $player->id)->delete();
            
            // Infine eliminiamo il record principale
            $player->delete();
        });

        return back()->with('message', 'Calciatore rimosso dal sistema.');
    }
}