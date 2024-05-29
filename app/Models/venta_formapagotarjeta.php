<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class venta_formapagotarjeta extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "venta_formapagotarjeta";
    protected $primaryKey = 'id_abono';

}
