<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class venta_formapagobanco extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "venta_formapagobanco";
    protected $primaryKey = 'id_abono';

}
