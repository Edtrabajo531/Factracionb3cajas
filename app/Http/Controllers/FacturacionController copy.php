<?php

namespace App\Http\Controllers;

use App\Models\Almapro;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Cliente;
use App\Models\Venta;
use App\Models\Ventadetalle;
use App\Models\Empresa;
use App\Models\Prepro;
use App\Models\formapago;
use App\Models\venta_formapago;
use App\Models\venta_formapagobanco;
use App\Models\venta_formapagotarjeta;
use App\Models\caja_efectivo;
use App\Models\caja_movimiento;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FacturacionController extends Controller
{

    public function validate_cash_status(Request $request)
    {
        $caja_efectivo = caja_movimiento::where('id_caja', $request->id)->where('estado', 0)->first();
        $status = "";
        if ($caja_efectivo) {
            $status = "ok";
        }
        //return response()->json(['status' => "ok"]);

        return response()->json(['status' => $status]);
    }


    public function data_print_invoice($nro_factura = Null)
    {
        $ventas = Venta::where('nro_factura', $nro_factura)->get();
        $cliente = Cliente::find($ventas->first()->id_cliente);
        // if ($ventas->count() == 2) {
        //     $total = $ventas->first()->montototal + $ventas->last()->montototal;
        //     $empresa1 = Empresa::find($ventas->first()->id_empresa);
        //     $empresa2 = Empresa::find($ventas->last()->id_empresa);
        //     $detalles1 = Ventadetalle::leftJoin('producto', 'producto.pro_id', '=', 'venta_detalle.id_producto')
        //         ->select('producto.pro_grabaiva as pro_grabaiva', 'producto.pro_nombre as pro_nombre', 'venta_detalle.*')
        //         ->where('id_venta', $ventas->first()->id_venta)->get();
        //     $detalles2 = Ventadetalle::leftJoin('producto', 'producto.pro_id', '=', 'venta_detalle.id_producto')
        //         ->select('producto.pro_grabaiva as pro_grabaiva', 'producto.pro_nombre as pro_nombre', 'venta_detalle.*')
        //         ->where('id_venta', $ventas->last()->id_venta)->get();
        // } else {
        // $total = $ventas->first()->montototal;
        $empresa1 = Empresa::find($ventas->first()->id_empresa);
        $empresa2 = "";
        $detalles1 = Ventadetalle::leftJoin('producto', 'producto.pro_id', '=', 'venta_detalle.id_producto')
            ->select('producto.pro_grabaiva as pro_grabaiva', 'producto.pro_nombre as pro_nombre', 'venta_detalle.*')
            ->where('id_venta', $ventas->first()->id_venta)->get();
        $detalles2 = [];
        // }

        $subtotalsiniva12 = 0;
        $subtotaliva0 = 0;
        $subtotaliva12 = 0;
        $subtotalconiva0 = 0;
        $montoiva = 0;
        $total = 0;
        $desc_monto = 0;
        foreach ($detalles1 as $p) {
            $desc_monto += $p['descmonto'];
            $total += $p->costo_total;
            // $subtotalsiniva12 += $p->precio;
            if ($p->pro_grabaiva == 1) {
                $subtotaliva12 += $p->costo_total;
                $montoiva += $p->montoiva;
            } else {
                $subtotaliva0 += $p->costo_total;
                $montoiva += $p->montoiva;
            }
        }

        // foreach ($detalles2 as $p) {
        //     $desc_monto += $p['descmonto'];

        //     $total += $p->costo_total;
        //     if ($p->pro_grabaiva == 1) {
        //         $subtotaliva12 += $p->costo_total;
        //         $montoiva += $p->montoiva;
        //     } else {
        //         $subtotaliva0 += $p->costo_total;
        //         $montoiva += $p->montoiva;
        //     }
        // }

        $fecha = $ventas->first()->fecha;
        $subtotaliva12D = $subtotaliva12 / 1.12;
        $iva = $subtotaliva12 - $subtotaliva12D;
        $subtotaliva12 = $subtotaliva12D;
        return compact('total', 'empresa1', 'empresa2', 'detalles1', 'detalles2', 'nro_factura', 'cliente', 'subtotaliva12', 'subtotaliva0', 'montoiva', 'desc_monto', 'fecha');

        /*$Empresa = Empresa::find($venta->id_empresa);*/
    }

    public function leading_zeros()
    {
        $number = 22;
        $string = substr(str_repeat(0, 8) . $number, -8);
        return $string;
    }

    public function search_product_code(Request $request)
    {
        // $product = Product::where('pro_codigobarra', $request->code)->first();
        $code = $request->code;
        $almacen = $request->almacen;
        $product = DB::select("SELECT p.pro_grabaiva, p.pro_id, p.pro_nombre, p.pro_precioventa, p.pro_codigobarra,
        null as pro_imagen, p.imagen_path, ap.existencia as existencia, ap.id_alm as almacen_id
        FROM producto p
        JOIN almapro ap ON ap.id_pro = p.pro_id
        WHERE p.pro_codigobarra = '$code' AND ap.id_alm = $almacen");

        if ($product == Null) {
            return response()->json(['result' => 'not exist']);
        } else {
            return response()->json(['result' => 'ok', 'data' => $product]);
        }
    }

    public function pay(Request $request)
    {
        if ($request->tipo_documento == 2) {
            $tipodocstring = "FACTURA DE VENTA";
            $punto_1 = "001-010-";
            $punto_2 = "000000001";
            $last_venta = Venta::where('tipo_doc', 2)->orderBy('id_venta', 'desc')->get()->first();
            if ($last_venta) {
                $length = 9;
                $punto_2 = substr($last_venta->nro_factura, -$length);
                $punto_2 = intval($punto_2) + 1;
                $punto_2 = substr(str_repeat(0, $length) . $punto_2, -$length);
                $numerofa = $punto_1 . $punto_2;
            } else {
                $numerofa = $punto_1 . $punto_2;
            }
        } else {
            $tipodocstring = "NOTA DE VENTA";

            $punto_2 = "000000001";
            $last_venta = Venta::where('tipo_doc', 1)->orderBy('id_venta', 'desc')->get()->first();
            if ($last_venta) {
                $length = 9;

                $punto_2 = intval($last_venta->nro_factura) + 1;
                $punto_2 = substr(str_repeat(0, $length) . $punto_2, -$length);
                $numerofa = $punto_2;
            } else {
                $numerofa = $punto_2;
            }
        }

        $caja_id = $request->caja_id;

        if ($caja_id == 1) {
            $punto = 1;
            $sucursal = 1;

        } else if ($caja_id == 10) {
            $punto = 10;
            $sucursal = 2;
        }

        $id_vendedor = 18;
        $cliente = Cliente::where('id_cliente', $request->id_cliente)->first();
        $totalGIVA = 0;
        $totalf = 0;
        // calculos f1 y f2

        foreach ($request->productos as $p) {
            if ($p['pro_grabaiva'] == 1) {
                $totalGIVA += $p['total'];
            }
            $totalf += $p['total'];
        }

        // Factura 1 graba iva
        $venta = new Venta;
        $venta->fecha = Carbon::now('-05:00')->format('Y-m-d');
        $venta->area = "";
        $venta->mesa = "";
        $venta->mesero = "";
        $venta->tipo_doc = $request->tipo_documento;
        $venta->nro_factura = $numerofa;
        $venta->tipo_ident = $cliente->tipo_ident_cliente;
        $venta->nro_ident = $cliente->ident_cliente;
        $venta->nom_cliente = $cliente->nom_cliente;
        $venta->telf_cliente = $cliente->telefonos_cliente;
        $venta->dir_cliente = $cliente->direccion_cliente;
        $venta->correo_cliente = $cliente->correo_cliente;
        $venta->ciu_cliente = $cliente->ciudad_cliente;
        $venta->valiva = 0.12;
        $venta->subconiva = $totalf / 1.12;
        $venta->subsiniva = 0;
        $venta->desc_monto = 0;
        $venta->descsubconiva = $totalf / 1.12;

        $venta->descsubsiniva = 0; //pendiente
        $venta->montoiva = $totalf - $venta->subconiva; //preguntar
        $venta->montototal = $totalf;
        $venta->fecharegistro = Carbon::now('-05:00')->format('Y-m-d H:i:s');
        $venta->idusu = 18; // ARREGLAR PARA QUE USE EL AUTENTICADO
        $venta->estatus = 1; //preguntar
        $venta->id_cliente = $request->id_cliente;
        $venta->id_tipcancelacion = 1; //preguntar
        $venta->montoimpuestoadicional = 0; //preguntar
        $venta->id_empresa = 1;
        $venta->id_sucursal = $sucursal;
        $venta->id_puntoemision = $punto; //preguntar
        $venta->id_caja = $caja_id; //preguntar
        $venta->nro_orden = Null; //preguntar
        $venta->cambio = 0; //preguntar
        $venta->id_vendedor = $id_vendedor; //preguntar
        $venta->observaciones = $request->observaciones;
        $venta->placa_matricula = Null; //preguntar
        $venta->idmesa = 0;
        $venta->save();
        // guardar detalles productos f1

        foreach ($request->productos as $p) {
            // if ($p['pro_grabaiva'] == 1) {
            $porcentajeiva = ($p['pro_precioventa'] * 12) / 100;
            $pro_precioventa = $p['pro_precioventa'] - $porcentajeiva;
            $pro_precioventa = number_format($pro_precioventa, 4);
            $detalle = new Ventadetalle;
            $detalle->id_venta = $venta->id_venta;
            $detalle->id_producto = $p['pro_id'];
            $detalle->cantidad = $p['cantidad'];
            $detalle->precio = $pro_precioventa;
            $detalle->subtotal = $pro_precioventa * $p['cantidad']; //preguntar
            $detalle->iva = 1;
            $detalle->montoiva = (($p['cantidad'] * $pro_precioventa) * 12) / 100;
            $detalle->descmonto = $p['descmonto'];
            $detalle->descsubtotal = $p['descsubtotal'];
            $detalle->id_almacen = 1; //añadir almacen
            $detalle->tipprecio = 0; //preguntar
            $detalle->porcdesc = $p['porcdesc'];
            $detalle->descripcion = $p['pro_nombre'];
            $detalle->subsidio = 1; //preguntar
            $detalle->costo_unitario = $pro_precioventa; //preguntar
            $detalle->costo_total = $p['total'];
            $detalle->save();

            $exist = DB::table('almapro')
                ->where('id_alm', $p['almacen_id'])
                ->where("id_pro", $p['pro_id'])
                ->select('existencia')->first();

            $exist = $exist->existencia;
            $exist = floatval($exist) - floatval($p['cantidad']);

            DB::table('almapro')
                ->where('id_alm', $p['almacen_id'])
                ->where("id_pro", $p['pro_id'])
                ->update([
                    'existencia' => $exist,
                ]);

            // $sqlid = $this->db->query("call kardexegreso_ins($idpro, '$documento', '$detalle',$cantidad, $valunit, $costototal, $unidad, $idusuario, $id_almacen);");
            //
            //

            DB::select('call kardexegreso_ins("' . $p['pro_id'] . '", "' . $venta->nro_factura . '","' . $tipodocstring . '","' . $p['cantidad'] . '","' . $pro_precioventa . '","' . $p['total'] . '","0","0","' . $p['almacen_id'] . '")');

            // }
        }

        foreach ($request->formasdepago as $fp) {
            $formapago = formapago::where('id_formapago', $fp['id_formapago'])->first();
            if ($formapago->esinstrumentobanco) {

                $lastFpv = venta_formapagobanco::orderBy('id_abono', 'desc')->first();
                if ($lastFpv) {
                    $lastFpv = $lastFpv->id_abono + 1;
                } else {
                    $lastFpv = 0;
                }

                $newFB = new venta_formapagobanco;
                $newFB->id_abono = $lastFpv;
                $newFB->id_banco = $fp['bank'];
                $newFB->fechaemision = $fp['date_of_issue'];
                $newFB->fechacobro = $fp['date_of_withdrawal'];
                $newFB->numerocuenta = $fp['number_account'];
                $newFB->numerodocumento = $fp['number_document'];
                $newFB->descripciondocumento = $fp['description_document'];
                $newFB->save();

                $new = new venta_formapago;
                $new->id_venta = $venta->id_venta;
                $new->id_formapago = $fp['id_formapago'];
                $new->monto = $fp['amount'];
                $new->fecha = $fp['date_of_issue'];
                $new->nro_comprobante = 1;
                $new->id_cajapago = 1;
                $new->save();
            } else if ($formapago->estarjeta) {

                $lastFpv = venta_formapagotarjeta::orderBy('id_abono', 'desc')->first();
                if ($lastFpv) {
                    $lastFpv = $lastFpv->id_abono + 1;
                } else {
                    $lastFpv = 0;
                }

                $newFB = new venta_formapagotarjeta;
                $newFB->id_abono = $lastFpv;
                $newFB->id_tarjeta = $fp['target'];
                $newFB->id_banco = $fp['bank'];

                $newFB->fechaemision = Carbon::now('-05:00')->format("Y-m-d H:i:s");
                $newFB->numerotarjeta = $fp['number_target'];

                $newFB->numerodocumento = $fp['number_document'];
                $newFB->descripciondocumento = $fp['description_document'];
                $newFB->save();

                $new = new venta_formapago;
                $new->id_venta = $venta->id_venta;
                $new->id_formapago = $fp['id_formapago'];
                $new->monto = $fp['amount'];
                $new->fecha = Carbon::now('-05:00')->format("Y-m-d H:i:s");
                ;
                $new->nro_comprobante = 1;
                $new->id_cajapago = 1;
                $new->save();
            } else {
                $new = new venta_formapago;
                $new->id_venta = $venta->id_venta;
                $new->id_formapago = $fp['id_formapago'];
                $new->monto = $fp['amount'];
                $new->fecha = Carbon::now('-05:00')->format("Y-m-d H:i:s");
                $new->nro_comprobante = 1;
                $new->id_cajapago = 1;
                $new->save();
            }
        }

        // $sqlid = $this->db->query("call kardexegreso_ins($idpro, '$documento', '$detalle',
        // $cantidad, $valunit, $costototal,
        // $unidad, $idusuario, $id_almacen);");

        $facturaprint = $this->data_print_invoice($venta->nro_factura);

        // formas de pago
        return response()->json(['result' => 'ok', 'message' => 'Documento pagado con éxito.', 'nro_factura' => $venta->nro_factura, "cancel_payment" => $formapago, "formasdepago" => $request->formasdepago, 'facturaprint' => $facturaprint]);
    }

    public function prueba()
    {
        return "PRUEBA";
    }

    public function list_productos(Request $request)
    {
        $caja_id = $request->caja_id;
        if ($caja_id == 1) {
            $almacen = 1;
        } else {
            $almacen = 2;
        }
        $productos = DB::select("SELECT p.pro_grabaiva, p.pro_id, p.pro_nombre, p.pro_precioventa, p.pro_codigobarra,
        null as pro_imagen, p.imagen_path, ap.existencia as existencia, ap.id_alm as almacen_id
        FROM producto p
        JOIN almapro ap ON ap.id_pro = p.pro_id
        WHERE ap.id_alm = $almacen");

        return response()->json($productos);
    }

    public function list_clientes()
    {
        $clientes = Cliente::all();
        return $clientes;
    }


    public function searchClient(Request $request)
    {
        $client = Cliente::where('tipo_ident_cliente', $request->type_ident)->where('ident_cliente', $request->ident)->first();
        return response()->json(["client" => $client, 'type_ident' => $request->type_ident]);
    }

    public function store_update_client(Request $request)
    {
        if ($request->id_cliente) {
            $validator = Validator::make($request->all(), [
                "tipo_ident_cliente" => 'required',
                "ident_cliente" => '',
                "nom_cliente" => 'required',
                "telefonos_cliente" => 'required',
                "correo_cliente" => 'required',
                "ciudad_cliente" => 'required',
                "direccion_cliente" => 'nullable',
                "placa_matricula" => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
            }

            $existIdent = Cliente::where('ident_cliente', $request->ident_cliente)->where('id_cliente', '!=', $request->id_cliente)->first();
            $existEmail = Cliente::where('correo_cliente', $request->correo_cliente)->where('id_cliente', '!=', $request->id_cliente)->first();
            if ($existIdent) {
                return response()->json(['result' => 'error-validation', 'errors' => json_encode(["email" => "La identificación ya está siendo utilizada."])]);
            }
            if ($existEmail) {
                return response()->json(['result' => 'error-validation', 'errors' => json_encode(["email" => "El correo ya está siendo utilizado."])]);
            }

            $cliente = Cliente::where('id_cliente', $request->id_cliente)->first();
            $cliente->tipo_ident_cliente = $request->tipo_ident_cliente;
            $cliente->ident_cliente = $request->ident_cliente;
            $cliente->nom_cliente = $request->nom_cliente;
            $cliente->telefonos_cliente = $request->telefonos_cliente;
            $cliente->correo_cliente = $request->correo_cliente;
            $cliente->ciudad_cliente = $request->ciudad_cliente;
            $cliente->direccion_cliente = $request->direccion_cliente;
            $cliente->placa_matricula = $request->placa_matricula;
            $cliente->save();

            return response()->json(['message' => 'Datos actualizados con éxito.', 'data' => $cliente]);
        } else {

            $validator = Validator::make($request->all(), [
                "tipo_ident_cliente" => 'required',
                "ident_cliente" => 'required',
                "nom_cliente" => 'required',
                "telefonos_cliente" => 'required',
                "correo_cliente" => 'required',
                "ciudad_cliente" => 'required',
                "direccion_cliente" => 'nullable',
                "placa_matricula" => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
            }

            $existIdent = Cliente::where('ident_cliente', $request->ident_cliente)->first();
            $existEmail = Cliente::where('correo_cliente', $request->correo_cliente)->first();
            if ($existIdent) {
                return response()->json(['result' => 'error-validation', 'errors' => json_encode(["ident_cliente" => "La identificación ya está siendo utilizada."])]);
            }
            if ($existEmail) {
                return response()->json(['result' => 'error-validation', 'errors' => json_encode(["correo_cliente" => "El correo ya está siendo utilizado."])]);
            }

            $cliente = new Cliente;
            $cliente->tipo_ident_cliente = $request->tipo_ident_cliente;
            $cliente->ident_cliente = $request->ident_cliente;
            $cliente->nom_cliente = $request->nom_cliente;
            $cliente->telefonos_cliente = $request->telefonos_cliente;
            $cliente->correo_cliente = $request->correo_cliente;
            $cliente->ciudad_cliente = $request->ciudad_cliente;
            $cliente->direccion_cliente = $request->direccion_cliente;
            $cliente->placa_matricula = $request->placa_matricula;
            $cliente->save();

            return response()->json(['message' => 'Usuario agregado con éxito.', 'data' => $cliente]);
        }
    }

    public function editar_usuario()
    {
    }
}


// $total = 0;
// $detalles1 = Ventadetalle::where('id_venta',$ventas->first()->id_venta)->get();
// foreach($detalles1 as $deta){
//     $total += $deta->costo_total;
// }

// $detalles2 = Ventadetalle::where('id_venta',$ventas->last()->id_venta)->get();

// foreach($detalles2 as $deta){
//     $total += $deta->costo_total;
// }
