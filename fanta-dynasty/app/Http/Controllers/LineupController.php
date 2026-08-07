<?php

namespace App\Http\Controllers;

use App\Models\{Roster, PrimaveraRoster, Lineup, LineupDetail, LeagueParticipant, RealPlayer};
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

        // Uniamo la rosa totale per il campo
        $fullRoster = $pros->concat($juniors)->map(function($item) {
            return [
                'id' => $item->player->id,
                'real_player_id' => $item->player->id,
                'name' => $item->player->name,
                'role' => $item->player->role,
                'real_team' => $item->player->real_team,
                'is_primavera' => !isset($item->contract_years)
            ];
        });

        // Carichiamo l'ultima formazione salvata
        $currentLineup = Lineup::where('league_id', $participant->league_id)
            ->where('user_id', $user->id)
            ->where('matchday', 1)
            ->with('details.player')
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
            'module' => 'required',
            'starters' => 'required'
        ]);

        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();

        // --- FIX: Parentesi corretta nella transazione ---
        DB::transaction(function () use ($request, $participant, $user) {
            $lineup = Lineup::updateOrCreate(
                ['league_id' => $participant->league_id, 'user_id' => $user->id, 'matchday' => $request->matchday],
                ['module' => $request->module]
            );

            // Puliamo i vecchi dettagli
            $lineup->details()->delete();

            // Salviamo i nuovi titolari
            // Trasformiamo l'oggetto starters della grafica in una lista di ID
            $playerIds = [];
            foreach ($request->starters as $role => $ids) {
                foreach ($ids as $id) {
                    if ($id) $playerIds[] = $id;
                }
            }

            foreach ($playerIds as $id) {
                LineupDetail::create([
                    'lineup_id' => $lineup->id,
                    'real_player_id' => $id,
                    'is_starter' => true
                ]);
            }

            // Salviamo i panchinari se presenti
            if ($request->has('bench')) {
                foreach ($request->bench as $index => $id) {
                    if ($id) {
                        LineupDetail::create([
                            'lineup_id' => $lineup->id,
                            'real_player_id' => $id,
                            'is_starter' => false,
                            'order' => $index + 1
                        ]);
                    }
                }
            }
        });

        return back()->with('message', 'Formazione salvata con successo!');
    }
}