<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ventadetalle extends Model
{
    use HasFactory;
    protected $primaryKey = 'id_detalle';
    public $timestamps = false;
    protected $table = "venta_detalle";
}
