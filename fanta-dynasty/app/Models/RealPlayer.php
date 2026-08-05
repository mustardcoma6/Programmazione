<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RealPlayer extends Model {
    protected $fillable = ['name', 'role', 'real_team', 'initial_value', 'quotation'];
}