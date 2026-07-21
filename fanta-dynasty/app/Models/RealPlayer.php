<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RealPlayer extends Model
{
    // Diciamo a Laravel quali dati può scrivere nel database
    protected $fillable = [
        'name', 
        'role', 
        'real_team', 
        'initial_value'
    ];
}