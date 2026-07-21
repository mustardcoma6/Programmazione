<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roster extends Model
{
    // Permettiamo a Laravel di scrivere in queste colonne
    protected $fillable = ['league_id', 'user_id', 'real_player_id', 'purchase_price', 'contract_years','release_clause'];

    // COLLEGAMENTO 1: Ogni acquisto appartiene a un Calciatore Reale
    public function player()
    {
        return $this->belongsTo(RealPlayer::class, 'real_player_id');
    }

    // COLLEGAMENTO 2: Ogni acquisto appartiene a un Utente (Fantallenatore)
    // QUESTO È IL PEZZO CHE MANCAVA!
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}