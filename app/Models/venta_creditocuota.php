<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class venta_creditocuota extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "venta_creditocuota";
    protected $primaryKey = 'id';
}
