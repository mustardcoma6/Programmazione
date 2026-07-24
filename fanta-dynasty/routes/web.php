<?php

use App\Http\Controllers\{ProfileController, LeagueController, PlayerController, MarketController, LineupController, TeamController};
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check()
        ? redirect('/dashboard')
        : redirect('/login');
});

Route::middleware(['auth'])->group(function () {
    
    // HOME
   Route::get('/dashboard', function () {
        $user = auth()->user();
        // Carichiamo le leghe dell'utente assicurandoci di prendere tutto
        $leagues = $user->leagues()->get(); 
        $firstLeague = $leagues->first();

        if ($firstLeague) {
            $myData = \App\Models\LeagueParticipant::where('league_id', $firstLeague->id)
                ->where('user_id', $user->id)
                ->first();

            $myPlayers = \App\Models\Roster::where('league_id', $firstLeague->id)
                ->where('user_id', $user->id)
                ->with('player')
                ->get();

            $currentLineup = \App\Models\Lineup::where('league_id', $firstLeague->id)
                ->where('user_id', $user->id)
                ->where('matchday', 1)
                ->with('details.player')
                ->first();

            $allParticipants = \App\Models\LeagueParticipant::where('league_id', $firstLeague->id)->get();
        } else {
            $myData = null; $myPlayers = []; $currentLineup = null; $allParticipants = [];
        }

        return Inertia::render('Dashboard', [
            'leagues' => $leagues,
            'myData' => $myData,
            'myPlayers' => $myPlayers,
            'currentLineup' => $currentLineup,
            'allParticipants' => $allParticipants
        ]);
    })->name('dashboard');

    // SQUADRE
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');

    // --- NUOVA ROTTA: ROSA ---
    Route::get('/rosa', [MarketController::class, 'myRosterPage'])->name('roster.index');

    // CALCIOMERCATO
    Route::get('/calciomercato', [MarketController::class, 'auctions'])->name('market.auctions');

    // SVINCOLATI
    Route::get('/players', [PlayerController::class, 'index'])->name('players.index');

    // GESTIONE MERCATO (Admin)
    Route::get('/admin/mercato/sessioni', [MarketController::class, 'sessions'])->name('market.sessions');
    Route::get('/admin/mercato/cronologia', [MarketController::class, 'history'])->name('market.history');
    Route::post('/market/sessions', [MarketController::class, 'storeSession'])->name('market.sessions.store');
    Route::post('/market/close-all/{league}', [MarketController::class, 'closeMarketNow'])->name('market.close-all');

    // AZIONI MERCATO
    Route::post('/buy-player', [MarketController::class, 'buy'])->name('players.buy');
    Route::post('/release-player', [MarketController::class, 'release'])->name('players.release');
    Route::post('/market/update-years', [MarketController::class, 'updateContract'])->name('market.update-years');

    // LEGA E CAMPO
    Route::get('/leagues/create', [LeagueController::class, 'create'])->name('leagues.create');
    Route::post('/leagues', [LeagueController::class, 'store'])->name('leagues.store');
    Route::get('/leagues/join', [LeagueController::class, 'join'])->name('leagues.join');
    Route::post('/leagues/join', [LeagueController::class, 'joinStore'])->name('leagues.join.store');
    Route::post('/leagues/{league}/toggle-market', [LeagueController::class, 'toggleMarket'])->name('leagues.market.toggle');
    Route::post('/leagues/{league}/update-market', [LeagueController::class, 'updateMarket'])->name('leagues.market.update');
    Route::get('/lineup', [LineupController::class, 'index'])->name('lineup.index');
    Route::post('/lineup', [LineupController::class, 'store'])->name('lineup.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';