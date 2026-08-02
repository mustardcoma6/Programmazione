<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Models\League;
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

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());
        if ($request->user()->isDirty('email')) { $request->user()->email_verified_at = null; }
        $request->user()->save();
        return Redirect::route('profile.edit');
    }

    public function updateTeamName(Request $request): RedirectResponse
    {
        $request->validate(['team_name' => 'required|string|max:255']);
        $participant = LeagueParticipant::where('user_id', auth()->id())->first();
        if ($participant) { $participant->update(['team_name' => $request->team_name]); }
        return Redirect::route('profile.edit');
    }

    /**
     * ELIMINA ACCOUNT (Versione Distruttiva Totale)
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user) {
            // 1. Se l'utente è ADMIN di una lega, cancelliamo l'intera lega
            // Questo eliminerà a cascata anche partecipanti e roster di altri utenti in quella lega
            League::where('admin_id', $user->id)->delete();

            // 2. Cancella la sua partecipazione come semplice utente in altre leghe
            LeagueParticipant::where('user_id', $user->id)->delete();

            // 3. Cancella i suoi calciatori
            Roster::where('user_id', $user->id)->delete();

            // 4. Cancella le sue aste e i suoi rilanci automatici
            Autobid::where('user_id', $user->id)->delete();
            Auction::where('user_id', $user->id)->delete();
            
            // 5. Cancella le sue formazioni
            $lineups = Lineup::where('user_id', $user->id)->get();
            foreach($lineups as $l) {
                LineupDetail::where('lineup_id', $l->id)->delete();
                $l->delete();
            }

            // 6. Logout e distruzione finale
            Auth::logout();
            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}