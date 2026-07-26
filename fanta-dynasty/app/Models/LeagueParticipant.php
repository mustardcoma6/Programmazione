<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeagueParticipant extends Model
{
    protected $fillable = ['league_id', 'user_id', 'team_name', 'remaining_budget', 'years_budget'];

    // COLLEGAMENTO 1: Ogni partecipante è un Utente (Presidente)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // COLLEGAMENTO 2: Ogni partecipante ha una lista di calciatori
    public function roster()
    {
        return $this->hasMany(Roster::class, 'user_id', 'user_id');
    }
}