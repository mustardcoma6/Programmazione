<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\LeagueParticipant;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Mostra la pagina del profilo, includendo i dati della squadra.
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
     * Aggiorna le informazioni base dell'utente (Nome reale, Email).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
{
    // Il comando fill() prende i dati validati dal file sopra e li mette nell'utente
    $request->user()->fill($request->validated());

    if ($request->user()->isDirty('email')) {
        $request->user()->email_verified_at = null;
    }

    // SALVATAGGIO REALE
    $request->user()->save();

    return Redirect::route('profile.edit')->with('message', 'Profilo aggiornato!');
}

    /**
     * NUOVA FUNZIONE: Aggiorna il Nome della Squadra.
     */
    public function updateTeamName(Request $request): RedirectResponse
    {
        $request->validate([
            'team_name' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();

        if ($participant) {
            $participant->update([
                'team_name' => $request->team_name
            ]);
        }

        return Redirect::route('profile.edit')->with('message', 'Nome squadra aggiornato con successo!');
    }

    /**
     * Elimina l'account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}