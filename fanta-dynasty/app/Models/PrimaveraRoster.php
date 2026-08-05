<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PrimaveraRoster extends Model {
    protected $fillable = ['league_id', 'user_id', 'real_player_id', 'purchase_price'];
    public function player() { return $this->belongsTo(RealPlayer::class, 'real_player_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
}