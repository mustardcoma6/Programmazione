<?php

namespace App\Http\Controllers;

use App\Models\{Roster, PrimaveraRoster, Lineup, LineupDetail, LeagueParticipant};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

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

        // Prepariamo una lista piatta e sicura per il selettore del campo
        $fullRoster = $pros->concat($juniors)->map(function($item) {
            return [
                'id' => $item->player->id,
                'name' => $item->player->name,
                'role' => $item->player->role,
                'is_primavera' => !isset($item->contract_years)
            ];
        })->values();

        return Inertia::render('Lineups/Index', [
            'roster' => $fullRoster,
            'matchday' => 1
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'module' => 'required|string',
            'starters' => 'required|array'
        ]);

        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();

        DB::transaction(function () use ($request, $participant, $user) {
            $lineup = Lineup::updateOrCreate(
                ['league_id' => $participant->league_id, 'user_id' => $user->id, 'matchday' => $request->matchday],
                ['module' => $request->module]
            );

            $lineup->details()->delete();

            // Salvataggio semplificato per evitare errori di loop
            foreach ($request->starters as $role => $ids) {
                foreach ($ids as $id) {
                    if ($id) {
                        LineupDetail::create([
                            'lineup_id' => $lineup->id,
                            'real_player_id' => $id,
                            'is_starter' => true
                        ]);
                    }
                }
            }
        });

        return redirect()->route('dashboard')->with('message', 'Formazione salvata!');
    }
}