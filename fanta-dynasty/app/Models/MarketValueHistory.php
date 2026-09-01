<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketValueHistory extends Model
{
    protected $table = 'market_value_histories'; // Forza il plurale

    protected $fillable = ['league_id', 'user_id', 'matchday', 'value', 'recorded_at'];
}