<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeagueParticipant extends Model
{
    protected $fillable = ['league_id', 'user_id', 'team_name', 'remaining_budget', 'years_budget'];

    // Relazione con l'utente (Presidente)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}