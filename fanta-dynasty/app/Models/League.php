<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    // Qui aggiungiamo i permessi per salvare i nuovi dati
    protected $fillable = [
        'name', 
        'invite_code', 
        'admin_id', 
        'initial_budget',
        'market_start_at', // <--- AGGIUNTO
        'market_end_at'    // <--- AGGIUNTO
    ];

    // Diciamo a Laravel che questi campi sono DATE
    protected $casts = [
        'market_start_at' => 'datetime',
        'market_end_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}