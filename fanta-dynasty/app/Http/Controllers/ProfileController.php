<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\LeagueParticipant;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
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
            'teamLogo' => $participant ? $participant->logo_path : null, // Passiamo il logo attuale
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }
        $request->user()->save();
        return Redirect::route('profile.edit');
    }

    // FUNZIONE PER AGGIORNARE IL NOME SQUADRA
    public function updateTeamName(Request $request): RedirectResponse
    {
        $request->validate(['team_name' => 'required|string|max:255']);
        $participant = LeagueParticipant::where('user_id', auth()->id())->first();
        if ($participant) {
            $participant->update(['team_name' => $request->team_name]);
        }
        return Redirect::route('profile.edit');
    }

    // FUNZIONE PER CARICARE IL LOGO (PUNTO 5)
    public function updateTeamLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = auth()->user();
        $participant = LeagueParticipant::where('user_id', $user->id)->first();

        if ($request->hasFile('logo') && $participant) {
            // Salviamo il file nel disco 'public'
            $path = $request->file('logo')->store('logos', 'public');
            
            // Generiamo l'URL completo (es. https://tuosito.railway.app/storage/logos/xxx.png)
            $fullUrl = asset('storage/' . $path);
            
            $participant->update([
                'logo_path' => $fullUrl
            ]);
        }

        return Redirect::route('profile.edit')->with('message', 'Logo aggiornato!');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $user = $request->user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return Redirect::to('/');
    }
}