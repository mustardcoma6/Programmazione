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
    // MOSTRA PAGINA CREA LEGA
    public function create()
    {
        return Inertia::render('Leagues/Create');
    }

    // SALVA NUOVA LEGA
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'initial_budget' => 'required|integer|min:100|max:1000',
        ]);

        $league = League::create([
            'name' => $request->name,
            'invite_code' => strtoupper(Str::random(8)),
            'admin_id' => auth()->id(),
            'initial_budget' => $request->initial_budget,
            'is_market_open' => false,
        ]);

        LeagueParticipant::create([
            'league_id' => $league->id,
            'user_id' => auth()->id(),
            'team_name' => 'Squadra di ' . auth()->user()->name,
            'remaining_budget' => $request->initial_budget,
        ]);

        return redirect()->route('dashboard');
    }

    // AGGIORNA DATE MERCATO (Asta a tempo)
    public function updateMarket(Request $request, League $league)
{
    // Debug: Solo l'admin può procedere
    if (auth()->id() !== (int)$league->admin_id) {
        return back()->withErrors(['error' => 'Non sei il presidente!']);
    }

    // Scrittura forzata colonna per colonna
    $league->market_start_at = $request->market_start_at;
    $league->market_end_at = $request->market_end_at;
    $league->save(); // Salva fisicamente

    return back();
}

    // MOSTRA PAGINA UNISCITI
    public function join()
    {
        return Inertia::render('Leagues/Join');
    }

    // SALVA UNIONE A LEGA
    public function joinStore(Request $request)
    {
        $request->validate([
            'invite_code' => 'required|string|exists:leagues,invite_code',
        ]);

        $league = League::where('invite_code', $request->invite_code)->first();

        $exists = LeagueParticipant::where('league_id', $league->id)
                    ->where('user_id', auth()->id())
                    ->exists();

        if ($exists) {
            return back()->withErrors(['invite_code' => 'Sei già in questa lega!']);
        }

        LeagueParticipant::create([
            'league_id' => $league->id,
            'user_id' => auth()->id(),
            'team_name' => 'Squadra di ' . auth()->user()->name,
            'remaining_budget' => $league->initial_budget,
        ]);

        return redirect()->route('dashboard');
    }
}