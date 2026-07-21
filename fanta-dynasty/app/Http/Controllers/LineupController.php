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
        $league = $user->leagues()->first();

        if (!$league) return redirect()->route('dashboard');

        // Recuperiamo la rosa dell'utente
        $myRoster = Roster::where('league_id', $league->id)
            ->where('user_id', $user->id)
            ->with('player')
            ->get();

        return Inertia::render('Lineups/Index', [
            'roster' => $myRoster,
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

        // Salviamo la testata (Modulo e Giornata)
        $lineup = Lineup::updateOrCreate(
            ['league_id' => $league->id, 'user_id' => $user->id, 'matchday' => $request->matchday],
            ['module' => $request->module]
        );

        // Puliamo la vecchia formazione
        $lineup->details()->delete();

        // Inseriamo i Titolari
        foreach ($request->starters as $playerId) {
            LineupDetail::create([
                'lineup_id' => $lineup->id,
                'real_player_id' => $playerId,
                'is_starter' => true,
                'order' => 0
            ]);
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