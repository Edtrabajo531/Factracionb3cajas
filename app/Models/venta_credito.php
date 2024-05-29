<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class venta_credito extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "venta_credito";
    protected $primaryKey = null;
    public $incrementing = false;

}
