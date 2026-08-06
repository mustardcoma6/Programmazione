<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeagueParticipant extends Model
{
    protected $fillable = [
        'league_id', 
        'user_id', 
        'team_name', 
        'remaining_budget', 
        'years_budget',
        'remaining_primavera_budget' // <--- SBLOCCATO
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function roster() {
        return $this->hasMany(Roster::class, 'user_id', 'user_id');
    }
}