<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LineupDetail extends Model {
    protected $fillable = ['lineup_id', 'real_player_id', 'is_starter', 'order'];
    public function player() { return $this->belongsTo(RealPlayer::class, 'real_player_id'); }
}