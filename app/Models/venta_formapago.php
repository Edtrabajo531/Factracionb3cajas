<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class venta_formapago extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "venta_formapago";
    protected $primaryKey = 'id';
}
