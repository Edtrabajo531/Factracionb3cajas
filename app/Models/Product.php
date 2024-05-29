<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "producto";



    public function precios()
    {
        return $this->hasMany("App\Models\Prepro","pro_id",'pro_id')->join("precios","prepro.id_precios","=", "precios.id_precios");
    }

}
// [
//     {"id_prepro":1,"pro_id":1,"id_precios":11,"monto":"0.000000","desc_precios":"PRECIO 2","esta_precios":"A","color":"#ff0000"},
//     {"id_prepro":2,"pro_id":1,"id_precios":12,"monto":"0.000000","desc_precios":"PRECIO 3","esta_precios":"A","color":"#ff0000"},
//     {"id_prepro":19415,"pro_id":1,"id_precios":13,"monto":"0.000000","desc_precios":"PRECIO DISTRIBUIDOR","esta_precios":"A","color":"#ff0000"}
//     ]
