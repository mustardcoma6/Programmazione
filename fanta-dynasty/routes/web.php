<?php

use App\Http\Controllers\{ProfileController, LeagueController, PlayerController, MarketController, LineupController, TeamController};
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () { return Inertia::render('Welcome'); });

Route::middleware(['auth'])->group(function () {
    
    // HOME
    Route::get('/dashboard', [LeagueController::class, 'dashboard'])->name('dashboard');

    // --- SOCIETÀ ---
    Route::get('/rosa', [MarketController::class, 'myRosterPage'])->name('roster.index');
    Route::get('/societa/formazione', function() { return redirect()->route('lineup.index'); })->name('roster.lineup');
    Route::get('/societa/finanze', [MarketController::class, 'financesPage'])->name('roster.finances');
    Route::get('/societa/primavera', [MarketController::class, 'primaveraPage'])->name('roster.primavera'); // NUOVA

    // --- LEGA ---
    Route::get('/societa', [LeagueController::class, 'societaIndex'])->name('societa.index');
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
    Route::get('/lega/ranking', function() { return redirect()->route('societa.index'); })->name('league.ranking');
    Route::get('/lega/sala-trofei', function() { return redirect()->route('societa.index'); })->name('league.trophies');
    // MERCATO
    Route::get('/calciomercato', [MarketController::class, 'auctions'])->name('market.auctions');
    Route::get('/admin/mercato/cronologia', [MarketController::class, 'history'])->name('market.history');
    Route::get('/admin/mercato/sessioni', [MarketController::class, 'sessions'])->name('market.sessions');

    // ADMIN
    Route::get('/admin/gestione-rose', [LeagueController::class, 'manageRosters'])->name('admin.rosters');
    Route::get('/admin/gestione-budget', [LeagueController::class, 'manageCredits'])->name('admin.credits');
    Route::get('/admin/gestione-listone', [PlayerController::class, 'adminIndex'])->name('admin.players');
    Route::get('/admin/gestione-finanze', [LeagueController::class, 'manageFinances'])->name('admin.finances');
    Route::post('/admin/players', [PlayerController::class, 'store'])->name('admin.players.store');
    Route::delete('/admin/players/{player}', [PlayerController::class, 'destroy'])->name('admin.players.destroy');
    Route::post('/admin/players/bulk-import', [PlayerController::class, 'bulkImport'])->name('admin.players.bulk-import');
    Route::post('/admin/players/mass-update', [PlayerController::class, 'massUpdateQuotations'])->name('admin.players.mass-update');
    Route::post('/admin/update-resources', [LeagueController::class, 'updateResources'])->name('admin.resources.update');
    Route::post('/admin/assign-player', [LeagueController::class, 'assignPlayer'])->name('admin.assign');
    Route::post('/admin/assign-manual', [LeagueController::class, 'assignManualPlayer'])->name('admin.assign.manual');
    Route::post('/admin/remove-player', [LeagueController::class, 'removePlayer'])->name('admin.remove');
    Route::delete('/admin/kick-participant/{participant}', [LeagueController::class, 'kickParticipant'])->name('admin.participant.kick');
Route::get('/admin/gestione-primavera', [LeagueController::class, 'managePrimavera'])->name('admin.primavera');
Route::post('/admin/primavera-assign', [LeagueController::class, 'assignPrimavera'])->name('admin.primavera.assign');
Route::post('/admin/primavera-remove', [LeagueController::class, 'removePrimavera'])->name('admin.primavera.remove');
    // AZIONI UTENTE
    Route::post('/buy-player', [MarketController::class, 'buy'])->name('players.buy');
    Route::post('/release-player', [MarketController::class, 'release'])->name('players.release');
    Route::post('/market/update-years', [MarketController::class, 'updateContract'])->name('market.update-years');
    Route::get('/lineup', [LineupController::class, 'index'])->name('lineup.index');
    Route::post('/lineup', [LineupController::class, 'store'])->name('lineup.store');
    Route::post('/market/sessions', [MarketController::class, 'storeSession'])->name('market.sessions.store');
    Route::post('/market/close-all/{league}', [MarketController::class, 'closeMarketNow'])->name('market.close-all');
    Route::post('/leagues/{league}/update-market', [LeagueController::class, 'updateMarket'])->name('leagues.market.update');

    // PROFILO / LEGA
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/team-name', [ProfileController::class, 'updateTeamName'])->name('profile.team.update');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/leagues/create', [LeagueController::class, 'create'])->name('leagues.create');
    Route::post('/leagues', [LeagueController::class, 'store'])->name('leagues.store');
    Route::get('/leagues/join', [LeagueController::class, 'join'])->name('leagues.join');
    Route::post('/leagues/join', [LeagueController::class, 'joinStore'])->name('leagues.join.store');
    Route::post('/leagues/{league}/toggle-market', [LeagueController::class, 'toggleMarket'])->name('leagues.market.toggle');
});

require __DIR__.'/auth.php';