<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;

class LeagueController extends Controller
{
    public function create()
    {
        return Inertia::render('Leagues/Create');
    }

    public function store(Request $request)
    {
        // Aggiunta validazione per team_name
        $request->validate([
            'name' => 'required|string|max:255',
            'initial_budget' => 'required|integer|min:100|max:1000',
            'team_name' => 'required|string|max:255', 
        ]);

        $league = League::create([
            'name' => $request->name,
            'invite_code' => strtoupper(Str::random(8)),
            'admin_id' => auth()->id(),
            'initial_budget' => $request->initial_budget,
            'is_market_open' => false,
        ]);

        // Usiamo il nome squadra scelto dall'utente
        LeagueParticipant::create([
            'league_id' => $league->id,
            'user_id' => auth()->id(),
            'team_name' => $request->team_name,
            'remaining_budget' => $request->initial_budget,
            'years_budget' => 40, // Budget Dynasty standard
        ]);

        return redirect()->route('dashboard');
    }

    public function join()
    {
        return Inertia::render('Leagues/Join');
    }

    public function joinStore(Request $request)
    {
        // Aggiunta validazione per team_name
        $request->validate([
            'invite_code' => 'required|string|exists:leagues,invite_code',
            'team_name' => 'required|string|max:255',
        ]);

        $league = League::where('invite_code', $request->invite_code)->first();

        $exists = LeagueParticipant::where('league_id', $league->id)
                    ->where('user_id', auth()->id())
                    ->exists();

        if ($exists) {
            return back()->withErrors(['invite_code' => 'Sei già iscritto a questa lega!']);
        }

        // Usiamo il nome squadra scelto dall'utente
        LeagueParticipant::create([
            'league_id' => $league->id,
            'user_id' => auth()->id(),
            'team_name' => $request->team_name,
            'remaining_budget' => $league->initial_budget,
            'years_budget' => 40,
        ]);

        return redirect()->route('dashboard');
    }

    public function updateMarket(Request $request, League $league)
    {
        if (auth()->id() !== $league->admin_id) return back();
        $league->market_start_at = $request->market_start_at;
        $league->market_end_at = $request->market_end_at;
        $league->save();
        return back();
    }
}