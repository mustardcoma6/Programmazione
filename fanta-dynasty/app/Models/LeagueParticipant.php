<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeagueParticipant extends Model
{
    use HasFactory;

    // Queste sono le "porte aperte" del database. 
    // Se un nome manca qui, Laravel NON salverà mai quel dato.
    protected $fillable = [
        'league_id',
        'user_id',
        'team_name',
        'remaining_budget',
        'years_budget',
        'league_points',  // ASSICURATI CHE CI SIA
        'total_points',   // ASSICURATI CHE CI SIA
        'games_played',   // ASSICURATI CHE CI SIA
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}