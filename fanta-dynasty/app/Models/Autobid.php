<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Autobid extends Model
{
    // Permettiamo a Laravel di scrivere in queste colonne
    protected $fillable = ['auction_id', 'user_id', 'max_bid'];
}