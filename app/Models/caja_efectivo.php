<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class caja_efectivo extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_caja';
    public $timestamps = false;
    protected $table = "caja_efectivo";
}
