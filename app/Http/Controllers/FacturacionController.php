<?php

namespace App\Http\Controllers;

use App\Models\Almapro;
use App\Models\Precio;
use App\Models\venta_credito;
use App\Models\venta_creditocuota;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FacturacionController extends Controller
{

    public function validate_cash_status(Request $request)
    {
        $caja_efectivo_exist = caja_movimiento::where('id_caja', $request->id)->first();
        if (!$caja_efectivo_exist) {
            $status = "no existe";
            return response()->json(['status' => $status]);
        }

        $caja_efectivo = caja_movimiento::where('id_caja', $request->id)->where('estado', 0)->first();
        $status = "";
        if ($caja_efectivo) {
            $status = "ok";
        }
        return response()->json(['status' => $status]);
    }


    public function data_print_invoice2($request, $id_venta, $nro_factura)
    {
        $venta = Venta::where('id_venta', $id_venta)->first();
        $cliente = Cliente::find($venta->id_cliente);
        $empresa1 = Empresa::find($venta->id_empresa);
        $detalles1 = $request->productos;

        $subtotaliva12 = 0;
        $subtotaliva0 = 0;
        $descuento = $request->totaldescuento;
        $totalIva = 0;
        $total = $request->total;

        foreach ($detalles1 as $index => $d) {
            if ($d["pro_grabaiva"]) {
                $iva = ($d["totalsiniva"]) * 15 / 100;
                $subtotaliva12 += ($d["totalsiniva"]);
                $totalIva += $iva;
            } else {
                $subtotaliva0 += ($d["totalsiniva"]);
            }
        }
        // SUBTOTAL IVA 12%:
        // SUBTOTAL IVA 0%:
        // DESCUENTO: 
        // IVA: 

        $fecha = $venta->fecha;
        return compact('empresa1', 'detalles1', 'cliente', 'venta', 'nro_factura', 'subtotaliva12', 'subtotaliva0', 'descuento', 'totalIva', 'total');
    }


    public function data_print_invoice($nro_factura = Null, $tipo_documento = Null, $caja_id = Null)
    {

        $venta = Venta::where('nro_factura', $nro_factura)->where('tipo_doc', $tipo_documento)->where('id_caja', $caja_id)->first();
        $cliente = Cliente::find($venta->id_cliente);

        $empresa1 = Empresa::find($venta->id_empresa);
        $empresa2 = "";

        $detalles1 = Ventadetalle::leftJoin('producto', 'producto.pro_id', '=', 'venta_detalle.id_producto')
            ->select('producto.pro_grabaiva as pro_grabaiva', 'producto.pro_nombre as pro_nombre', 'venta_detalle.*')
            ->where('id_venta', $venta->id_venta)->get();

        $subtotalsiniva12 = 0;
        $subtotaliva0 = 0;
        $subtotaliva12 = 0;
        $subtotalconiva0 = 0;
        $montoiva = 0;
        $total = 0;

        $fecha = $venta->fecha;
        $desc_monto = $venta->desc_monto;
        $subtotaliva12D = $subtotaliva12 / 1.15;
        $iva = $subtotaliva12 - $subtotaliva12D;

        return compact('total', 'empresa1', 'empresa2', 'detalles1', 'nro_factura', 'cliente', 'venta', 'subtotaliva0');
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

        $caja_id = $request->caja_id;
        if ($caja_id == 1) {
            $almacen = 1;
        } else {
            $almacen = 1;
        }
        $code = $request->code;

        $product = Product::join("almapro", "almapro.id_pro", "=", "producto.pro_id")
            ->select("producto.*", "almapro.existencia as existencia", "almapro.id_alm as almacen_id")
            ->where("id_alm", $almacen)
            ->where("pro_codigobarra", $code)
            ->orWhere(function (Builder $query) use ($almacen, $code) {
                $query->where("id_alm", $almacen)
                    ->where("pro_codigoauxiliar", $code);
            })
            ->with([
                'precios' => function ($query) {
                    // Realizar la manipulación adicional aquí
                    $query->selectRaw('*, ROUND(monto * 1.15, 2) as monto');
                }
            ])
            ->first();

        if ($product == Null) {
            return response()->json(['result' => 'not exist']);
        } else {
            return response()->json(['result' => 'ok', 'data' => $product]);
        }
    }

    public function pay(Request $request)
    {

        $request->caja_url = $request->caja_id;
        
        $caja1 = new \stdClass;
        $caja2 = new \stdClass;
        $caja3 = new \stdClass;
        $caja1->productos = [];
        $caja1->caja_id = 1;
        $caja1->punto_id = 1;
        $caja1->punto = "001-010-";

        $caja1->sucursal_id = 1;
        $caja1->empresa_id = 1;

        $caja2->productos = [];
        $caja2->caja_id = 11;
        $caja2->punto_id = 10;
        $caja2->punto = "001-010-";
        $caja2->sucursal_id = 2;
        $caja2->empresa_id = 2;

        $caja3->productos = [];
        $caja3->caja_id = 12;
        $caja3->punto_id = 11;
        $caja3->punto = "001-010-";

        $caja3->sucursal_id = 3;
        $caja3->empresa_id = 3;

        foreach ($request->productos as $p) {

            if ($p["caja_id"] == 1) {
                $caja1->productos[] = $p;
            } else if ($p["caja_id"] == 11) {
                $caja2->productos[] = $p;
            } else if ($p["caja_id"] == 12) {
                $caja3->productos[] = $p;
            }
        }

        $requestAll = clone $request;

        $r1 = $this->crearFactura($request, $caja1);
        $r2 = $this->crearFactura($request, $caja2);
        $r3 = $this->crearFactura($request, $caja3);

        $rp = "";
        if (count($caja1->productos) != 0) {
            $rp = $r1;
        } else if (count($caja2->productos) != 0) {
            $rp = $r2;
        } else {
            $rp = $r3;
        }

        $facturaprint = $this->data_print_invoice2($requestAll, $rp->id_venta, $rp->nro_factura);

        return response()->json(['result' => 'ok', 'message' => 'Documento pagado con éxito.', 'nro_factura' => $rp->nro_factura, 'facturaprint' => $facturaprint]);
    }

    public function crearFactura($request, $caja)
    {

        if (count($caja->productos) == 0) {
            return;
        }

        $request->productos = $caja->productos;
        $request->caja_id = $caja->caja_id;
        $caja_id = $caja->caja_id;
        $punto_1 = $caja->punto;
        $punto = $caja->punto_id;
        $sucursal = $caja->sucursal_id;
        $empresa_id = $caja->empresa_id;

        if ($request->tipo_documento == 2) {
            $tipodocstring = "FACTURA DE VENTA";
            $punto_2 = "000000001";
            $last_venta = Venta::where('tipo_doc', 2)->where('id_caja', $caja_id)->orderBy('id_venta', 'desc')->get()->first();
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
            $last_venta = Venta::where('tipo_doc', 1)->where('id_caja', $caja_id)->orderBy('id_venta', 'desc')->get()->first();
            if ($last_venta) {
                $length = 9;
                $punto_2 = intval($last_venta->nro_factura) + 1;
                $punto_2 = substr(str_repeat(0, $length) . $punto_2, -$length);
                $numerofa = $punto_2;
            } else {
                $numerofa = $punto_2;
            }
        }

        $id_vendedor = 18;
        $cliente = Cliente::where('id_cliente', $request->id_cliente)->first();
        $totalf = 0;
        $totalDescuento = 0;
        // calculos f1 y f2
        foreach ($request->productos as $p) {
            $value = floatval($p["pro_precioventa"]);
            $totalf += $value * $p["cantidad"];
            $totalDescuento += $p["descmonto"];
        }

        // { "pro_id": 80002, "pro_preciocompra": "5.130000", "pro_precioventa": "12.9024", "pro_maximo": "0.00", "pro_minimo": "0.00", "pro_idcategoria": "0", "pro_iddeducible": "0", "pro_grabaiva": 1, "pro_esservicio": 0, "pro_estatus": "A", "pro_imagen": "", "pro_idunidadmedida": 1, "pro_aplicompra": 1, "pro_apliventa": 1, "habilitavariante": 0, "productodescontarventa": 0, "maxitemvariante": "0", "comanda": 1, "cantidad": 1, "idcla": 31, "preparado": 0, "ingrediente": 0, "id_cto_retencion": 0, "pro_garantia": 0, "ubicacion": "", "subsidio": "0.000000", "idcategoriacontable": 3, "imagen_path": "0", "ruc": 1, "marca": 0, "caja_id": 12, "existencia": "4.00", "almacen_id": 1, "precios": [ { "id_prepro": 3, "pro_id": 80002, "id_precios": 11, "monto": "8.90", "desc_precios": "PRECIO 1", "esta_precios": "A", "color": "#ff0000" }, { "id_prepro": 361, "pro_id": 80002, "id_precios": 12, "monto": "9.90", "desc_precios": "PRECIO 2", "esta_precios": "A", "color": "#ff0000" } ], "typeDesc": "dollar", "desc": 0, "descmonto": 0, "descPorcentage": 0, "porcdesc": 0, "descsubtotal": 0, "precioOriginal": "12.9024", "price_correct": "12.9024", "type_price": "original", "totalsiniva": 11.52, "totalsiniva_nodesc": 11.52, "total": 12.9024, "totalNodesc": 12.9024 } ] 

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
        $venta->valiva = 0.15;
        $venta->subconiva = $totalf / 1.15;
        $venta->subsiniva = 0;
        $venta->desc_monto = $totalDescuento;
        $venta->descsubconiva = ($totalf / 1.15) - $totalDescuento;
        $venta->descsubsiniva = 0; //pendiente
        $venta->montoiva = ($totalf / 1.15) * 0.15; //preguntar
        $venta->montototal = $totalf;
        $venta->fecharegistro = Carbon::now('-05:00')->format('Y-m-d H:i:s');
        $venta->idusu = 18; // ARREGLAR PARA QUE USE EL AUTENTICADO
        $venta->estatus = 1; //preguntar
        $venta->id_cliente = $request->id_cliente;
        $venta->id_tipcancelacion = 1; //preguntar
        $venta->montoimpuestoadicional = 0; //preguntar
        $venta->id_empresa = $empresa_id;
        $venta->id_sucursal = $sucursal;
        $venta->id_puntoemision = $punto; //preguntar
        $venta->id_caja = $caja_id; //preguntar
        $venta->nro_orden = Null; //preguntar
        $venta->cambio = 0; //preguntar
        $venta->id_vendedor = $id_vendedor; //preguntar
        $venta->observaciones = $request->observaciones;
        $venta->placa_matricula = Null; //preguntar
        $venta->idmesa = 0;
        $venta->caja_url = $request->caja_url;
        $venta->save();

        foreach ($request->productos as $p) {

            $detalle = new Ventadetalle;
            $detalle->id_venta = $venta->id_venta;
            $detalle->id_producto = $p['pro_id'];
            $detalle->cantidad = $p['cantidad'];
            $detalle->precio = $p['totalsiniva'] / $p['cantidad'];
            $detalle->subtotal = $p['totalsiniva']; //preguntar
            $detalle->iva = 1;
            $detalle->montoiva = $p['totalsiniva'] * 0.15;
            $detalle->descmonto = $p['descmonto'];
            $detalle->descsubtotal = $p['totalsiniva'] - $p['descmonto'];
            $detalle->id_almacen = 1; //añadir almacen
            $detalle->tipprecio = 0; //preguntar
            $detalle->porcdesc = $p['porcdesc'];
            $detalle->descripcion = $p['pro_nombre'];
            $detalle->subsidio = 1; //preguntar
            $detalle->costo_unitario = $p['pro_precioventa']; //preguntar
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

            if (!strpos(url()->current(), 'localhost')) {
                DB::select('call kardexegreso_ins("' . $p['pro_id'] . '", "' . $venta->nro_factura . '","' . $tipodocstring . '","' . $p['cantidad'] . '","' . $p['pro_precioventa'] . '","' . $p['total'] . '","0","0","' . $p['almacen_id'] . '")');
            }

        }


        foreach ($request->formasdepago as $fp) {
            if ($fp['type'] == 'CRÉDITO') {

                $venta_creditocuota = new venta_credito;
                $venta_creditocuota->id_venta = $venta->id_venta;
                $venta_creditocuota->fechalimite = $fp['fechalimite'];
                $venta_creditocuota->dias = $fp['dias'];
                $venta_creditocuota->p100interes_credito = $fp['p100interes_credito'];
                $venta_creditocuota->p100interes_mora = $fp['p100interes_mora'];
                $venta_creditocuota->cantidadcuotas = $fp['cantidadcuotas'];
                $venta_creditocuota->abonoinicial = $fp['abonoinicial'];
                $venta_creditocuota->montobasecredito = $totalf;
                $venta_creditocuota->montointerescredito = $fp['montointerescredito'];
                $venta_creditocuota->montocredito = $totalf;
                $venta_creditocuota->montobasemora = null;
                $venta_creditocuota->montointeresmora = null;
                $venta_creditocuota->id_estado = 3;
                $venta_creditocuota->save();

                $venta_credito = new venta_creditocuota;
                $venta_credito->id_venta = $venta->id_venta;
                $venta_credito->fechalimite = $fp['fechalimite'];
                $venta_credito->monto = $totalf;
                $venta_credito->pagado = $fp['abonoinicial'];
                $venta_credito->save();

            } else if ($fp['type'] == 'CONTADO') {
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
                    $new->monto = $totalf;
                    $new->fecha = Carbon::now('-05:00')->format("Y-m-d H:i:s");
                    $new->nro_comprobante = 1;

                    $new->id_cajapago = $caja_id;
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
                    $new->monto = $totalf;
                    $new->fecha = Carbon::now('-05:00')->format("Y-m-d H:i:s");
                    $new->nro_comprobante = 1;

                    $new->id_cajapago = $caja_id;

                    $new->save();
                } else {
                    $new = new venta_formapago;
                    $new->id_venta = $venta->id_venta;
                    $new->id_formapago = $fp['id_formapago'];
                    $new->monto = $totalf;
                    $new->fecha = Carbon::now('-05:00')->format("Y-m-d H:i:s");
                    $new->nro_comprobante = 1;
                    $new->id_cajapago = $caja_id;

                    $new->save();
                }
            }

        }


        $values = new \stdClass;
        $values->nro_factura = $numerofa;
        $values->caja_id = $caja_id;
        $values->id_venta = $venta->id_venta;

        return $values;
    }

    public function prueba()
    {
        return dd(strpos(url()->current(), 'localhost'));
    }

    public function list_productos(Request $request)
    {

        $caja_id = $request->caja_id;
        if ($caja_id == 1) {
            $almacen = 1;
        } else {
            $almacen = 1;
        }

        $precios = Precio::get();

        $productos = Product::join("almapro", "almapro.id_pro", "=", "producto.pro_id")
            ->select("producto.*", "almapro.existencia as existencia", "almapro.id_alm as almacen_id")
            ->where("id_alm", $almacen)
            ->with("precios")
            ->get();


        return response()->json(["productos" => $productos, "precios" => $precios]);
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

}
