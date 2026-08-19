<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MarketValueHistory extends Model {
    // Specifichiamo la tabella per evitare confusioni
    protected $table = 'market_value_histories'; 
    protected $fillable = ['league_id', 'user_id', 'value', 'recorded_at'];
}