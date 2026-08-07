<?php

use App\Http\Controllers\{ProfileController, LeagueController, PlayerController, MarketController, LineupController, TeamController};
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Carbon\Carbon;

Route::get('/', function () { return Inertia::render('Welcome'); });

Route::middleware(['auth'])->group(function () {
    
    // HOME (DASHBOARD) - LOGICA RANKING PRESIDENTI
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $leagues = $user->leagues()->get(); 
        $firstLeague = $leagues->first();
        $myData = null; $myPlayers = []; $currentLineup = null; $allParticipants = []; $isMarketOpen = false; $stats = null;

        if ($firstLeague) {
            $myData = \App\Models\LeagueParticipant::where('league_id', $firstLeague->id)->where('user_id', $user->id)->first();
            $myPlayers = \App\Models\Roster::where('league_id', $firstLeague->id)->where('user_id', $user->id)->with('player')->get();
            $currentLineup = \App\Models\Lineup::where('league_id', $firstLeague->id)->where('user_id', $user->id)->where('matchday', 1)->with('details.player')->first();
            $allParticipants = \App\Models\LeagueParticipant::where('league_id', $firstLeague->id)->orderBy('remaining_budget', 'desc')->get();
            $isMarketOpen = \App\Models\MarketSession::where('league_id', $firstLeague->id)->where('start_at', '<=', now())->where('end_at', '>=', now())->exists();

            // --- CLASSIFICA UFFICIALE GIORNATA 1 ---
            $ranking = [
                'SANTOS' => 49,
                'BOTAFOGO' => 44,
                'PALMEIRAS' => 43,
                'ATLETICO G MINEIRO' => 36,
                'VASCO DE GAMA' => 30,
                'CORINTHIANS' => 30,
                'FLAMENGO' => 26,
                'CRUZEIRO E.C.' => 18,
                'FLUMINENSE' => 0,
                'SAO PAULO' => 0
            ];
            
            arsort($ranking);
            $sortedPresidents = array_keys($ranking);
            $myTeamClean = strtoupper(trim($user->name));
            $pos = array_search($myTeamClean, $sortedPresidents);
            $generalRank = ($pos !== false) ? ($pos + 1) : '-';
            
            // Cerchiamo il nome dell'utente (Presidente) nella classifica
            $userNameClean = strtoupper(trim($user->name));
            $pos = array_search($userNameClean, $sortedPresidents);
            $generalRank = ($pos !== false) ? ($pos + 1) : '-';

            // Ranking Crediti (Economico)
            $creditRank = \App\Models\LeagueParticipant::where('league_id', $firstLeague->id)->where('remaining_budget', '>', $myData->remaining_budget)->count() + 1;

            $stats = [
                'topPlayer' => \App\Models\Roster::where('user_id', $user->id)->where('league_id', $firstLeague->id)->with('player')->orderBy('purchase_price', 'desc')->first()?->player->name ?? 'Nessuno',
                'topPrice' => \App\Models\Roster::where('user_id', $user->id)->where('league_id', $firstLeague->id)->orderBy('purchase_price', 'desc')->first()?->purchase_price ?? 0,
                'rank' => $creditRank,
                'generalRank' => $generalRank,
                'totalParticipants' => count($allParticipants)
            ];
        }

        return Inertia::render('Dashboard', ['leagues' => $leagues, 'myData' => $myData, 'myPlayers' => $myPlayers, 'currentLineup' => $currentLineup, 'allParticipants' => $allParticipants, 'isMarketOpen' => (bool)$isMarketOpen, 'stats' => $stats]);
    })->name('dashboard');

    // ROTTE LEGA
    Route::get('/societa', [LeagueController::class, 'societaIndex'])->name('societa.index');
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::get('/lega/ranking', [LeagueController::class, 'rankingIndex'])->name('league.ranking');
    Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
    Route::get('/lega/sala-trofei', function() { return redirect()->route('societa.index'); })->name('league.trophies');

    // ROTTE SOCIETÀ
    Route::get('/rosa', [MarketController::class, 'myRosterPage'])->name('roster.index');
    Route::get('/societa/formazione', function() { return redirect()->route('lineup.index'); })->name('roster.lineup');
    Route::get('/societa/finanze', [MarketController::class, 'financesPage'])->name('roster.finances');
    Route::get('/societa/primavera', [MarketController::class, 'primaveraPage'])->name('roster.primavera');

    // CALCIOMERCATO & ADMIN
    Route::get('/calciomercato', [MarketController::class, 'auctions'])->name('market.auctions');
    Route::get('/admin/mercato/cronologia', [MarketController::class, 'history'])->name('market.history');
    Route::get('/admin/mercato/sessioni', [MarketController::class, 'sessions'])->name('market.sessions');
    Route::get('/admin/gestione-rose', [LeagueController::class, 'manageRosters'])->name('admin.rosters');
    Route::get('/admin/gestione-budget', [LeagueController::class, 'manageCredits'])->name('admin.credits');
    Route::get('/admin/gestione-listone', [PlayerController::class, 'adminIndex'])->name('admin.players');
    Route::get('/admin/gestione-finanze', [LeagueController::class, 'manageFinances'])->name('admin.finances');
    Route::get('/admin/gestione-primavera', [LeagueController::class, 'managePrimavera'])->name('admin.primavera');
    
    // AZIONI
    Route::post('/admin/players', [PlayerController::class, 'store'])->name('admin.players.store');
    Route::delete('/admin/players/{player}', [PlayerController::class, 'destroy'])->name('admin.players.destroy');
    Route::post('/admin/players/bulk-import', [PlayerController::class, 'bulkImport'])->name('admin.players.bulk-import');
    Route::post('/admin/players/mass-update', [PlayerController::class, 'massUpdateQuotations'])->name('admin.players.mass-update');
    Route::post('/admin/update-resources', [LeagueController::class, 'updateResources'])->name('admin.resources.update');
    Route::post('/admin/assign-player', [LeagueController::class, 'assignPlayer'])->name('admin.assign');
    Route::post('/admin/assign-manual', [LeagueController::class, 'assignManualPlayer'])->name('admin.assign.manual');
    Route::post('/admin/remove-player', [LeagueController::class, 'removePlayer'])->name('admin.remove');
    Route::delete('/admin/kick-participant/{participant}', [LeagueController::class, 'kickParticipant'])->name('admin.participant.kick');
    Route::post('/admin/primavera-assign', [LeagueController::class, 'assignPrimavera'])->name('admin.primavera.assign');
    Route::post('/admin/primavera-remove', [LeagueController::class, 'removePrimavera'])->name('admin.primavera.remove');
    Route::post('/buy-player', [MarketController::class, 'buy'])->name('players.buy');
    Route::post('/release-player', [MarketController::class, 'release'])->name('players.release');
    Route::post('/market/update-years', [MarketController::class, 'updateContract'])->name('market.update-years');
    Route::get('/lineup', [LineupController::class, 'index'])->name('lineup.index');
    Route::post('/lineup', [LineupController::class, 'store'])->name('lineup.store');
    Route::post('/market/sessions', [MarketController::class, 'storeSession'])->name('market.sessions.store');
    Route::post('/market/close-all/{league}', [MarketController::class, 'closeMarketNow'])->name('market.close-all');
    Route::post('/leagues/{league}/update-market', [LeagueController::class, 'updateMarket'])->name('leagues.market.update');

    // PROFILO
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