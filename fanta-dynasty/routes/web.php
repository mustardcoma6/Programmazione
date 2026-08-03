<?php

use App\Http\Controllers\{ProfileController, LeagueController, PlayerController, MarketController, LineupController, TeamController};
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () { return Inertia::render('Welcome'); });

Route::middleware(['auth'])->group(function () {
    
    // HOME
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $leagues = $user->leagues()->get(); 
        $firstLeague = $leagues->first();
        $myData = null; $myPlayers = []; $currentLineup = null; $allParticipants = []; $isMarketOpen = false;

        if ($firstLeague) {
            $myData = \App\Models\LeagueParticipant::where('league_id', $firstLeague->id)->where('user_id', $user->id)->first();
            $myPlayers = \App\Models\Roster::where('league_id', $firstLeague->id)->where('user_id', $user->id)->with('player')->get();
            $currentLineup = \App\Models\Lineup::where('league_id', $firstLeague->id)->where('user_id', $user->id)->where('matchday', 1)->with('details.player')->first();
            $allParticipants = \App\Models\LeagueParticipant::where('league_id', $firstLeague->id)->get();
            $isMarketOpen = \App\Models\MarketSession::where('league_id', $firstLeague->id)->where('start_at', '<=', now())->where('end_at', '>=', now())->exists();
        }

        return Inertia::render('Dashboard', ['leagues' => $leagues, 'myData' => $myData, 'myPlayers' => $myPlayers, 'currentLineup' => $currentLineup, 'allParticipants' => $allParticipants, 'isMarketOpen' => (bool)$isMarketOpen]);
    })->name('dashboard');

    // --- SOCIETÀ (ANAGRAFE) ---
    Route::get('/societa', [LeagueController::class, 'societaIndex'])->name('societa.index');
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::get('/rosa', [MarketController::class, 'myRosterPage'])->name('roster.index');

    // --- CALCIOMERCATO ---
    Route::get('/calciomercato', [MarketController::class, 'auctions'])->name('market.auctions');
    Route::get('/admin/mercato/sessioni', [MarketController::class, 'sessions'])->name('market.sessions');
    Route::get('/admin/mercato/cronologia', [MarketController::class, 'history'])->name('market.history');

    // --- GESTIONE ADMIN ---
    Route::get('/admin/gestione-rose', [LeagueController::class, 'manageRosters'])->name('admin.rosters');
    Route::get('/admin/gestione-budget', [LeagueController::class, 'manageCredits'])->name('admin.credits');
    Route::get('/admin/gestione-listone', [PlayerController::class, 'adminIndex'])->name('admin.players');
    Route::post('/admin/players', [PlayerController::class, 'store'])->name('admin.players.store');
    Route::delete('/admin/players/{player}', [PlayerController::class, 'destroy'])->name('admin.players.destroy');
    Route::post('/admin/update-resources', [LeagueController::class, 'updateResources'])->name('admin.resources.update');
    Route::post('/admin/assign-player', [LeagueController::class, 'assignPlayer'])->name('admin.assign');
    Route::post('/admin/assign-manual', [LeagueController::class, 'assignManualPlayer'])->name('admin.assign.manual');
    Route::post('/admin/remove-player', [LeagueController::class, 'removePlayer'])->name('admin.remove');
    Route::delete('/admin/kick-participant/{participant}', [LeagueController::class, 'kickParticipant'])->name('admin.participant.kick');

    // --- AZIONI UTENTE ---
    Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
    Route::post('/buy-player', [MarketController::class, 'buy'])->name('players.buy');
    Route::post('/release-player', [MarketController::class, 'release'])->name('players.release');
    Route::post('/market/update-years', [MarketController::class, 'updateContract'])->name('market.update-years');
    Route::get('/lineup', [LineupController::class, 'index'])->name('lineup.index');
    Route::post('/lineup', [LineupController::class, 'store'])->name('lineup.store');
    Route::post('/market/sessions', [MarketController::class, 'storeSession'])->name('market.sessions.store');
    Route::post('/market/close-all/{league}', [MarketController::class, 'closeMarketNow'])->name('market.close-all');

    // --- PROFILO ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/team-name', [ProfileController::class, 'updateTeamName'])->name('profile.team.update');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- LEGA ---
    Route::get('/leagues/create', [LeagueController::class, 'create'])->name('leagues.create');
    Route::post('/leagues', [LeagueController::class, 'store'])->name('leagues.store');
    Route::get('/leagues/join', [LeagueController::class, 'join'])->name('leagues.join');
    Route::post('/leagues/join', [LeagueController::class, 'joinStore'])->name('leagues.join.store');
    Route::post('/leagues/{league}/toggle-market', [LeagueController::class, 'toggleMarket'])->name('leagues.market.toggle');
    Route::post('/leagues/{league}/update-market', [LeagueController::class, 'updateMarket'])->name('leagues.market.update');
});

require __DIR__.'/auth.php';