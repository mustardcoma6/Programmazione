<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeagueParticipant extends Model
{
    protected $fillable = ['league_id', 'user_id', 'team_name', 'remaining_budget', 'years_budget'];

    // Relazione: Una squadra ha molti calciatori nel roster
    public function roster()
    {
        return $this->hasMany(Roster::class, 'user_id', 'user_id');
    }
}