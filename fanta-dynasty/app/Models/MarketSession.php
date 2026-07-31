<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketSession extends Model
{
    // FONDAMENTALE: Aggiungiamo i permessi di scrittura
    protected $fillable = [
        'league_id', 
        'start_at', 
        'end_at', 
        'auction_duration', 
        'allowed_roles'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];
}