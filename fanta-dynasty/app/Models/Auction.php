<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Auction extends Model {
    protected $fillable = ['league_id', 'real_player_id', 'user_id', 'current_bid', 'expires_at', 'is_finished'];
    protected $casts = ['expires_at' => 'datetime'];

    public function player() { return $this->belongsTo(RealPlayer::class, 'real_player_id'); }
    public function user() { return $this->belongsTo(User::class); }
    // Aggiungi questa funzione dentro la classe Auction
public function autobids() {
    return $this->hasMany(Autobid::class);
}
}