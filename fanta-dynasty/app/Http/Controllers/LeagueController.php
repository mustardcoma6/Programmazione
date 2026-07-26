<?php

namespace App\Http\Controllers;

use App\Models\{League, LeagueParticipant, Roster};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LeagueController extends Controller
{
    // NUOVA FUNZIONE: PAGINA SOCIETÀ
    public function societaIndex()
    {
        $user = auth()->user();
        $league = $user->leagues()->first();
        if (!$league) return redirect()->route('dashboard');

        // Prendiamo tutti i partecipanti
        $participants = LeagueParticipant::where('league_id', $league->id)
            ->with('user') // Per vedere il nome reale del proprietario
            ->get();

        // Calcoliamo per ogni partecipante quanti anni ha usato
        foreach ($participants as $p) {
            $p->years_used = Roster::where('league_id', $league->id)
                ->where('user_id', $p->user_id)
                ->sum('contract_years');
        }

        return Inertia::render('Societa/Index', [
            'league' => $league,
            'participants' => $participants
        ]);
    }

    public function create() { return Inertia::render('Leagues/Create'); }

    public function store(Request $request) {
        $request->validate(['name' => 'required', 'initial_budget' => 'required', 'team_name' => 'required']);
        $league = League::create(['name' => $request->name, 'invite_code' => strtoupper(Str::random(8)), 'admin_id' => auth()->id(), 'initial_budget' => $request->initial_budget]);
        LeagueParticipant::create(['league_id' => $league->id, 'user_id' => auth()->id(), 'team_name' => $request->team_name, 'remaining_budget' => $request->initial_budget, 'years_budget' => 40]);
        return redirect()->route('dashboard');
    }

    public function join() { return Inertia::render('Leagues/Join'); }

    public function joinStore(Request $request) {
        $request->validate(['invite_code' => 'required', 'team_name' => 'required']);
        $league = League::where('invite_code', $request->invite_code)->first();
        if (!$league) return back()->withErrors(['invite_code' => 'Codice errato']);
        LeagueParticipant::create(['league_id' => $league->id, 'user_id' => auth()->id(), 'team_name' => $request->team_name, 'remaining_budget' => $league->initial_budget, 'years_budget' => 40]);
        return redirect()->route('dashboard');
    }

    public function toggleMarket(League $league) {
        if (auth()->id() !== $league->admin_id) return back();
        $league->update(['is_market_open' => !$league->is_market_open]);
        return back();
    }

    public function updateMarket(Request $request, League $league) {
        if (auth()->id() !== $league->admin_id) return back();
        $league->update(['market_start_at' => $request->market_start_at, 'market_end_at' => $request->market_end_at]);
        return back();
    }
}