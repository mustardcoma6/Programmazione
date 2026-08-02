<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\LeagueParticipant;
use App\Models\Roster;
use App\Models\Auction;
use App\Models\Autobid;
use App\Models\Lineup;
use App\Models\LineupDetail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Mostra la pagina del profilo.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'teamName' => $participant ? $participant->team_name : null,
        ]);
    }

    /**
     * Aggiorna le informazioni personali.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Aggiorna il nome della squadra.
     */
    public function updateTeamName(Request $request): RedirectResponse
    {
        $request->validate(['team_name' => 'required|string|max:255']);
        $participant = LeagueParticipant::where('user_id', auth()->id())->first();
        if ($participant) {
            $participant->update(['team_name' => $request->team_name]);
        }
        return Redirect::route('profile.edit');
    }

    /**
     * ELIMINA ACCOUNT (Con pulizia totale dei dati)
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // --- OPERAZIONE PULIZIA (CASCADE MANUALE) ---
        DB::transaction(function () use ($user) {
            // 1. Cancella i suoi calciatori nel roster
            Roster::where('user_id', $user->id)->delete();

            // 2. Cancella la sua partecipazione alla lega e i budget
            LeagueParticipant::where('user_id', $user->id)->delete();

            // 3. Cancella i suoi rilanci automatici (Autobid)
            Autobid::where('user_id', $user->id)->delete();

            // 4. Gestione Aste: se era il leader, l'asta rimane ma l'utente scompare
            // (In alternativa puoi cancellare le aste dove lui è leader)
            Auction::where('user_id', $user->id)->delete();
            
            // 5. Cancella le formazioni schierate
            $lineups = Lineup::where('user_id', $user->id)->pluck('id');
            LineupDetail::whereIn('lineup_id', $lineups)->delete();
            Lineup::where('user_id', $user->id)->delete();

            // 6. Logout e Cancellazione Utente
            Auth::logout();
            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}