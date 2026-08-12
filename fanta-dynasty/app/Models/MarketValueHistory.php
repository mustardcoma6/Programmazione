<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MarketValueHistory extends Model {
    protected $fillable = ['league_id', 'user_id', 'value', 'recorded_at'];
}