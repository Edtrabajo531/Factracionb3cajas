<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class caja_movimiento extends Model
{
    use HasFactory;

    protected $table = "caja_movimiento";
    protected $primaryKey = 'id_mov';
    public $timestamps = false;
}
