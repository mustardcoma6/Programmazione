<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auction extends Model
{
    protected $fillable = ['league_id', 'real_player_id', 'user_id', 'current_bid', 'expires_at', 'is_finished'];

    // FONDAMENTALE: Forza il trattamento come data
    protected $casts = [
        'expires_at' => 'datetime',
        'is_finished' => 'boolean'
    ];

    public function player() {
        return $this->belongsTo(RealPlayer::class, 'real_player_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}