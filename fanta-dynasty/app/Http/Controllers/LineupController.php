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

        $pros = Roster::where('league_id', $participant->league_id)->where('user_id', $user->id)->with('player')->get();
        $juniors = PrimaveraRoster::where('league_id', $participant->league_id)->where('user_id', $user->id)->with('player')->get();

        $fullRoster = $pros->concat($juniors)->map(function($item) {
            return [
                'id' => $item->player->id,
                'name' => $item->player->name,
                'role' => $item->player->role,
                'real_team' => $item->player->real_team,
                'nationality' => $item->player->nationality ?? 'Italia',
                'is_primavera' => !isset($item->contract_years)
            ];
        })->values();

        // Carichiamo l'ultima formazione con i dettagli e i dati dei giocatori reali
        $savedLineup = Lineup::where('league_id', $participant->league_id)
            ->where('user_id', $user->id)
            ->where('matchday', 1)
            ->with('details.player')
            ->first();

        return Inertia::render('Lineups/Index', [
            'roster' => $fullRoster,
            'savedLineup' => $savedLineup,
            'matchday' => 1
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();

        DB::transaction(function () use ($request, $participant, $user) {
            $lineup = Lineup::updateOrCreate(
                ['league_id' => $participant->league_id, 'user_id' => $user->id, 'matchday' => $request->matchday],
                ['module' => $request->module]
            );

            $lineup->details()->delete();

            // Salva Titolari con la loro posizione (Key)
            foreach ($request->starters as $role => $slots) {
                foreach ($slots as $index => $playerId) {
                    if ($playerId) {
                        LineupDetail::create([
                            'lineup_id' => $lineup->id,
                            'real_player_id' => $playerId,
                            'is_starter' => true,
                            'position_key' => $role . '_' . $index
                        ]);
                    }
                }
            }

            // Salva Panchina
            foreach ($request->bench as $index => $playerId) {
                if ($playerId) {
                    LineupDetail::create([
                        'lineup_id' => $lineup->id,
                        'real_player_id' => $playerId,
                        'is_starter' => false,
                        'order' => $index + 1
                    ]);
                }
            }
        });

        return back()->with('message', 'Formazione salvata!');
    }
}