<?php

namespace App\Http\Controllers;

use App\Models\Roster;
use App\Models\Lineup;
use App\Models\LineupDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LineupController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();
        if (!$participant) return redirect()->route('dashboard');

        // Prendiamo i Pro e i Primavera
        $pros = Roster::where('league_id', $participant->league_id)->where('user_id', $user->id)->with('player')->get();
        $juniors = PrimaveraRoster::where('league_id', $participant->league_id)->where('user_id', $user->id)->with('player')->get();

        // Uniamo la rosa totale per il selettore
        $fullRoster = $pros->concat($juniors)->map(function($item) {
            return [
                'id' => $item->player->id,
                'name' => $item->player->name,
                'role' => $item->player->role,
                'is_primavera' => isset($item->purchase_price) && !isset($item->contract_years) // Semplice check
            ];
        });

        // Carichiamo l'ultima formazione salvata
        $currentLineup = Lineup::where('league_id', $participant->league_id)
            ->where('user_id', $user->id)
            ->where('matchday', 1)
            ->with('details')
            ->first();

        return Inertia::render('Lineups/Index', [
            'roster' => $fullRoster,
            'savedLineup' => $currentLineup,
            'matchday' => 1
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'matchday' => 'required|integer',
            'module' => 'required|string',
            'starters' => 'required|array|size:11',
            'bench' => 'required|array|max:7', // Massimo 7 panchinari
        ]);

        $user = auth()->user();
        $league = $user->leagues()->first();

        DB::transaction function () use ($request, $participant, $user) {
            $lineup = Lineup::updateOrCreate(
                ['league_id' => $participant->league_id, 'user_id' => $user->id, 'matchday' => $request->matchday],
                ['module' => $request->module]
            );

        $lineup->details()->delete();

            foreach ($request->starters as $roleGroup => $playerIds) {
                foreach ($playerIds as $id) {
                    if ($id) {
                        LineupDetail::create([
                            'lineup_id' => $lineup->id,
                            'real_player_id' => $id,
                            'is_starter' => true
                        ]);
                    }
                }
            }
        }


        // Inseriamo i Panchinari (mantenendo l'ordine di scelta)
        foreach ($request->bench as $index => $playerId) {
            LineupDetail::create([
                'lineup_id' => $lineup->id,
                'real_player_id' => $playerId,
                'is_starter' => false,
                'order' => $index + 1
            ]);
        }

        return redirect()->route('dashboard')->with('message', 'Formazione schierata con successo!');
    }
}