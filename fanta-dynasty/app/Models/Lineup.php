<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Lineup extends Model {
    protected $fillable = ['league_id', 'user_id', 'matchday', 'module'];
    public function details() { return $this->hasMany(LineupDetail::class); }
}