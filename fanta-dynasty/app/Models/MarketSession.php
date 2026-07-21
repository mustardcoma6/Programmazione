<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketSession extends Model
{
    protected $fillable = [
        'league_id', 
        'start_at', 
        'end_at'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];
}