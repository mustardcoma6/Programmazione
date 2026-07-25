<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * I campi che possono essere scritti nel database.
     */
    protected $fillable = [
        'name',     // <--- QUESTO DEVE ESSERE QUI
        'email',
        'password',
    ];

    /**
     * I campi nascosti nelle risposte API.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Trasformazione automatica dei dati.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relazione con le Leghe (Tua modifica precedente)
     */
    public function leagues()
    {
        return $this->belongsToMany(League::class, 'league_participants', 'user_id', 'league_id')
                    ->withPivot('team_name', 'remaining_budget');
    }
}