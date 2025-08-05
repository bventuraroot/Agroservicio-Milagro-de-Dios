<?php

namespace App\Http\Controllers;

use App\Models\Ambiente;
use App\Models\Client;
use App\Models\Company;
use App\Models\Dte;
use App\Models\Sale;
use App\Models\Config;
use App\Models\Salesdetail;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Session;
use App\Mail\EnviarCorreo;
use App\Models\Correlativo;
use Illuminate\Http\JsonResponse;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $id_user = auth()->user()->id;
        // Consultar el rol del usuario (asumiendo que el rol de admin tiene role_id = 1)
        $rolQuery = "SELECT a.role_id FROM model_has_roles a WHERE a.model_id = ?";
        $rolResult = DB::select($rolQuery, [$id_user]);
        $isAdmin = !empty($rolResult) && $rolResult[0]->role_id == 1;

        $sales = Sale::join('typedocuments', 'typedocuments.id', '=', 'sales.typedocument_id')
            ->join('clients', 'clients.id', '=', 'sales.client_id')
            ->join('companies', 'companies.id', '=', 'sales.company_id')
            ->leftjoin('dte', 'dte.sale_id', '=', 'sales.id')
            ->select(
                'sales.*',
                'typedocuments.description AS document_name',
                'clients.firstname',
                'clients.firstlastname',
                'clients.name_contribuyente as nameClient',
                'clients.tpersona',
                'clients.email as mailClient',
                'companies.name AS company_name',
                'dte.tipoDte',
                'dte.estadoHacienda',
                'dte.id_doc',
                'dte.company_name',
                \DB::raw('(SELECT dee.descriptionMessage FROM dte dee WHERE dee.id_doc_Ref2=sales.id) AS relatedSale'),
                \DB::raw('CASE
                    WHEN sales.totalamount IS NULL OR sales.totalamount = 0 THEN
                        COALESCE((SELECT SUM(sd.nosujeta + sd.exempt + sd.pricesale + sd.detained13 - sd.renta - sd.detained)
                                 FROM salesdetails sd WHERE sd.sale_id = sales.id), 0)
                    ELSE sales.totalamount
                END AS calculated_total'));
        // Si no es admin, solo muestra los clientes ingresados por él
        if (!$isAdmin) {
            $sales->where('sales.user_id', $id_user);
        }

        // Obtener los clientes filtrados
        $sales = $sales->get();
        return view('sales.index', array(
            "sales" => $sales
        ));
    }

    public function impdoc($corr)
    {
        return view('sales.impdoc', array("corr" => $corr));
    }

    /**
     * Generar ticket de 80mm para impresión
     */
    public function printTicket(Request $request, $id)
    {
        try {

            // Verificar que el ID sea válido
            if (!$id || !is_numeric($id)) {
                throw new \Exception("ID de venta inválido: $id");
            }

            // Buscar la venta con todas las relaciones necesarias
            $sale = Sale::with([
                'client',
                'company',
                'typedocument',
                'details',
                'details.product',
                'details.product.marca'
            ])->find($id);

            if (!$sale) {
                throw new \Exception("Venta con ID $id no encontrada");
            }

            // Calcular totales de forma segura
            $subtotal = 0;
            $totalIva = 0;
            $total = 0;
            foreach ($sale->details as $detail) {
                $subtotal += $detail->pricesale + $detail->nosujeta + $detail->exempt;
                $totalIva += $detail->detained13;
            }

            $total = $subtotal + $totalIva;

            // Verificar si debe auto-imprimir
            $autoprint = $request->query('autoprint', 'true') !== 'false';

            // Usar vista minimal si hay problemas con la normal
            $view = $request->query('minimal') === 'true' ? 'sales.ticket-minimal' : 'sales.ticket';

            return view($view, compact('sale', 'subtotal', 'totalIva', 'total', 'autoprint'));

        } catch (\Exception $e) {
            \Log::error("Error generando ticket para venta ID $id: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());

            // Si es una petición AJAX, devolver JSON
            if ($request->ajax()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Error al generar el ticket: ' . $e->getMessage()
                ], 500);
            }

            // Si no es AJAX, redirigir con error
            return back()->with('error', 'Error al generar el ticket: ' . $e->getMessage());
        }
    }

    /**
     * Obtener información de impresoras del servidor (si está disponible)
     */
    public function getPrinterInfo()
    {
        try {
            $printerInfo = [
                'server_printers' => [],
                'recommendations' => [
                    'width' => '80mm',
                    'type' => 'thermal',
                    'margin' => '0mm'
                ],
                'common_80mm_printers' => [
                    'Epson TM-T88V',
                    'Epson TM-T88VI',
                    'Star TSP650II',
                    'Bixolon SRP-350plusIII',
                    'Citizen CT-S310A',
                    'POS-80 Series'
                ]
            ];

            // En sistemas Windows, podrías intentar ejecutar comandos del sistema
            // para obtener información de impresoras (requiere permisos especiales)
            if (PHP_OS_FAMILY === 'Windows') {
                $printerInfo['os'] = 'Windows';
                $printerInfo['note'] = 'Use las propiedades de impresora en Windows para configurar papel de 80mm';
            } else {
                $printerInfo['os'] = PHP_OS_FAMILY;
                $printerInfo['note'] = 'Configure su impresora térmica como predeterminada del sistema';
            }

            return response()->json($printerInfo);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo obtener información de impresoras del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar ticket en formato ESC/POS RAW para impresoras térmicas
     */
    public function printTicketRaw(Request $request, $id)
    {
        try {
            // Verificar que el ID sea válido
            if (!$id || !is_numeric($id)) {
                throw new \Exception("ID de venta inválido: $id");
            }

            // Buscar la venta
            $sale = Sale::with([
                'client',
                'company',
                'typedocument',
                'details',
                'details.product',
                'details.product.marca'
            ])->find($id);

            if (!$sale) {
                throw new \Exception("Venta con ID $id no encontrada");
            }

            // Generar contenido RAW ESC/POS
            $escpos = $this->generateESCPOS($sale);

            // Devolver como archivo de descarga que se puede enviar directamente a la impresora
            return response($escpos)
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="ticket_' . $id . '.prn"')
                ->header('Cache-Control', 'no-cache');

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error al generar ticket RAW: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar comandos ESC/POS para impresora térmica
     */
    private function generateESCPOS($sale)
    {
        $esc = "\x1B";  // ESC
        $gs = "\x1D";   // GS

        $escpos = "";

        // Inicializar impresora
        $escpos .= $esc . "@";  // Reset

        // Configurar papel de 80mm
        $escpos .= $esc . "D\x02\x05\x08\x00";  // Tabs

        // Centrar y nombre de empresa
        $escpos .= $esc . "a\x01";  // Centrar
        $escpos .= $esc . "!\x30";  // Doble alto y ancho
        $escpos .= ($sale->company->name ?? 'EMPRESA') . "\n";
        $escpos .= $esc . "!\x00";  // Tamaño normal

        // Información de empresa
        if ($sale->company->address) {
            $escpos .= $sale->company->address . "\n";
        }
        if ($sale->company->phone) {
            $escpos .= "Tel: " . $sale->company->phone . "\n";
        }

        // Línea separadora
        $escpos .= str_repeat("-", 48) . "\n";

        // Alinear a la izquierda
        $escpos .= $esc . "a\x00";

        // Info del ticket
        $escpos .= "TICKET #" . $sale->id . "\n";
        $escpos .= "Fecha: " . $sale->created_at->format('d/m/Y H:i:s') . "\n";
        $escpos .= "Cliente: " . ($sale->client->firstname ?? 'CLIENTE GENERAL') . "\n";

        $escpos .= str_repeat("-", 48) . "\n";

        // Productos
        $subtotal = 0;
        $totalIva = 0;

        foreach ($sale->details as $detail) {
            $subtotal += $detail->pricesale + $detail->nosujeta + $detail->exempt;
            $totalIva += $detail->detained13;

            $name = ($detail->product->name ?? 'Producto') . ' ' . ($detail->product->marca->name ?? '');
            if (strlen($name) > 32) {
                $name = substr($name, 0, 32);
            }

            $escpos .= $name . "\n";
            $escpos .= sprintf("  %d x $%.2f = $%.2f\n",
                $detail->amountp ?? 1,
                $detail->priceunit ?? 0,
                $detail->pricesale ?? 0
            );
        }

        $escpos .= str_repeat("-", 48) . "\n";

        // Totales
        $escpos .= sprintf("%-30s $%.2f\n", "Subtotal:", $subtotal);
        $escpos .= sprintf("%-30s $%.2f\n", "IVA:", $totalIva);
        $escpos .= str_repeat("=", 48) . "\n";

        // Total en grande
        $escpos .= $esc . "!\x20";  // Doble alto
        $escpos .= sprintf("TOTAL: $%.2f\n", $subtotal + $totalIva);
        $escpos .= $esc . "!\x00";  // Normal

        // Footer
        $escpos .= "\n";
        $escpos .= $esc . "a\x01";  // Centrar
        $escpos .= "¡GRACIAS POR SU COMPRA!\n";
        $escpos .= "Conserve este ticket\n";
        $escpos .= "\n\n\n";

        // Cortar papel
        $escpos .= $gs . "V\x41\x03";  // Corte total

        return $escpos;
    }

    /**
     * Imprimir ticket directamente en impresora sin diálogos
     */
    public function printTicketDirectToprinter(Request $request, $id)
    {
        try {
            // Verificar que el ID sea válido
            if (!$id || !is_numeric($id)) {
                throw new \Exception("ID de venta inválido: $id");
            }

            // Buscar la venta
            $sale = Sale::with([
                'client',
                'company',
                'typedocument',
                'details',
                'details.product',
                'details.product.marca'
            ])->find($id);

            if (!$sale) {
                throw new \Exception("Venta con ID $id no encontrada");
            }

            // Generar contenido ESC/POS para impresión directa
            $escpos = $this->generateESCPOS($sale);

            // Intentar imprimir directamente en la impresora del sistema
            $printResult = $this->sendToPrinter($escpos, $id);

            if ($printResult['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket enviado a la impresora correctamente',
                    'printer' => $printResult['printer'] ?? 'Predeterminada'
                ]);
            } else {
                // Si falla, devolver el contenido para descargar
                return response($escpos)
                    ->header('Content-Type', 'application/octet-stream')
                    ->header('Content-Disposition', 'attachment; filename="ticket_' . $id . '.prn"');
            }

        } catch (\Exception $e) {
            \Log::error("Error imprimiendo ticket directamente: " . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error al imprimir directamente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar datos directamente a la impresora del sistema
     */
    private function sendToPrinter($data, $ticketId)
    {
        try {
            // Detectar sistema operativo
            $os = PHP_OS_FAMILY;

            // Crear archivo temporal
            $tempFile = sys_get_temp_dir() . '/ticket_' . $ticketId . '_' . time() . '.prn';
            file_put_contents($tempFile, $data);

            $success = false;
            $printer = '';

            if ($os === 'Windows') {
                // Windows: Intentar imprimir con diferentes métodos
                $commands = [
                    'copy "' . $tempFile . '" LPT1:', // Puerto paralelo
                    'copy "' . $tempFile . '" PRN',   // Impresora predeterminada
                    'print "' . $tempFile . '"'       // Comando print
                ];

                foreach ($commands as $cmd) {
                    $output = [];
                    $returnVar = 0;
                    exec($cmd . ' 2>&1', $output, $returnVar);

                    if ($returnVar === 0) {
                        $success = true;
                        $printer = 'Windows - ' . explode(' ', $cmd)[0];
                        break;
                    }
                }

            } elseif ($os === 'Linux' || $os === 'Darwin') {
                // Linux/Mac: Usar lp command
                $commands = [
                    'lp "' . $tempFile . '"',                    // Impresora predeterminada
                    'lp -d "POS-80" "' . $tempFile . '"',        // Impresora específica
                    'cat "' . $tempFile . '" > /dev/usb/lp0',   // Puerto USB directo
                ];

                foreach ($commands as $cmd) {
                    $output = [];
                    $returnVar = 0;
                    exec($cmd . ' 2>&1', $output, $returnVar);

                    if ($returnVar === 0) {
                        $success = true;
                        $printer = 'Unix - lp';
                        break;
                    }
                }
            }

            // Limpiar archivo temporal
            @unlink($tempFile);

            return [
                'success' => $success,
                'printer' => $printer,
                'os' => $os
            ];

        } catch (\Exception $e) {
            \Log::error("Error enviando a impresora: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar ticket en formato HTML para impresión directa sin preview
     */
    public function printTicketDirect(Request $request, $id)
    {
        try {
            // Verificar que el ID sea válido
            if (!$id || !is_numeric($id)) {
                throw new \Exception("ID de venta inválido: $id");
            }

            // Buscar la venta
            $sale = Sale::with([
                'client',
                'company',
                'typedocument',
                'details',
                'details.product',
                'details.product.marca'
            ])->find($id);

            if (!$sale) {
                throw new \Exception("Venta con ID $id no encontrada");
            }

            // Crear un HTML optimizado para impresión directa
            $html = view('sales.ticket-direct', compact('sale'))->render();

            return response($html)
                ->header('Content-Type', 'text/html; charset=utf-8')
                ->header('Cache-Control', 'no-cache');

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error al generar ticket directo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function savefactemp($idsale, $clientid, $productid, $cantidad, $price, $pricenosujeta, $priceexenta, $pricegravada, $ivarete13, $renta, $ivarete, $acuenta, $fpago, $fee, $reserva, $ruta, $destino, $linea, $canal)
    {
        //\Log::info("savefactemp llamado con idsale: " . $idsale);

        DB::beginTransaction();

        try {
            $id_user = auth()->user()->id;
            //\Log::info("savefactemp: Usuario ID: " . $id_user);

            // Validar que el idsale no esté vacío
            if (!$idsale || $idsale == 'null' || $idsale == '') {
                throw new \Exception("ID de venta no válido: " . $idsale);
            }

            //\Log::info("savefactemp: Buscando venta con ID: " . $idsale);

            // Verificar si la venta existe en la base de datos
            $sale = Sale::find($idsale);
            //\Log::info("savefactemp: Resultado de búsqueda de venta: " . ($sale ? 'ENCONTRADA' : 'NO ENCONTRADA'));

            // Si no se encuentra, hacer un query adicional para verificar
            if (!$sale) {
                //\Log::info("savefactemp: Verificando existencia de venta con query directo");
                $saleExists = \DB::table('sales')->where('id', $idsale)->first();
                //\Log::info("savefactemp: Query directo resultado: " . ($saleExists ? 'EXISTE' : 'NO EXISTE'));

                if ($saleExists) {
                    //\Log::info("savefactemp: Venta existe en DB pero Eloquent no la encuentra. Datos: " . json_encode($saleExists));
                }
            }

            // Validar que la venta existe
            if (!$sale) {
                throw new \Exception("No se encontró la venta con ID: " . $idsale);
            }

            // Validar que el cliente no esté vacío
            if (!$clientid || $clientid == 'null' || $clientid == '0') {
                throw new \Exception("Debe seleccionar un cliente válido");
            }

            $sale->client_id = $clientid;
            $sale->acuenta = $acuenta;
            $sale->waytopay = $fpago;
            $sale->save();
            //$iva_calculado = round($price/1.13,2);
            //$preciogravado = round($iva_calculado*$cantidad,2);
            //$ivafac = round($pricegravada-($pricegravada/1.13),2);
            //precio unitario
            //iva fac
            $ivafac = round($pricegravada - ($pricegravada / 1.13), 2);
            //precio gravado
            $pricegravadafac = round($pricegravada / 1.13, 3);
            //precio unitario evaluar si es gravada sino solamente es el precio unitario
            if ($pricegravada != "0.00") {
                $priceunitariofac = round($pricegravadafac / $cantidad, 3);
            } else {
                $priceunitariofac = round($price, 3);
            }
            if ($sale->typedocument_id == '8') {
                $priceunitariofac = $price;
                $pricegravadafac = $pricegravada;
            }
            //$ivarete13 = round($pricegravada * 0.13, 2);
            //$ivafac = round($pricegravada - ($pricegravada / 1.13), 2);
            if ($sale->typedocument_id == '8') {
                $ivafac = 0.00;
            }
            //iva al fee
            $feesiniva = round($fee / 1.13, 2);
            $ivafee = round($fee - $feesiniva, 2);
            $saledetails = new Salesdetail();
            $saledetails->sale_id = $idsale;
            $saledetails->product_id = $productid;
            $saledetails->amountp = $cantidad;
            //$saledetails->priceunit = ($sale->typedocument_id==6) ? round($iva_calculado,2) : $price;
            $saledetails->priceunit = ($sale->typedocument_id == '6' || $sale->typedocument_id == '8') ? round($priceunitariofac, 2) : $price;
            //$saledetails->pricesale = ($sale->typedocument_id==6) ? round($preciogravado,2) : $pricegravada;
            $saledetails->pricesale = ($sale->typedocument_id == '6' || $sale->typedocument_id == '8') ? round($pricegravadafac, 2) : $pricegravada;
            $saledetails->nosujeta = $pricenosujeta;
            $saledetails->exempt = $priceexenta;
            $saledetails->detained13 = ($sale->typedocument_id == '6' || $sale->typedocument_id == '8') ? round($ivafac, 2) : $ivarete13;
            $saledetails->detained = $ivarete;
            $saledetails->renta = ($sale->typedocument_id != '8') ? round(0.00, 2) : round($renta * $cantidad, 2);
            $saledetails->fee = $feesiniva;
            $saledetails->feeiva = $ivafee;
            $saledetails->reserva = $reserva;
            $saledetails->ruta = $ruta;
            $saledetails->destino = $destino;
            $saledetails->linea = $linea;
            $saledetails->canal = $canal;
            $saledetails->user_id = $id_user;
            $saledetails->save();

            // Recalcular y actualizar el totalamount de la venta
            $this->updateSaleTotalAmount($idsale);

            DB::commit();
            return response()->json(array(
                "res" => "1",
                "idsaledetail" => $saledetails['id']
            ), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudo procesar la venta', 'message' => $e->getMessage()], 500);
        }
    }

    public function newcorrsale($iddoc): JsonResponse
    {
        DB::beginTransaction();

        try {
            $iduser = auth()->user()->id;
            //\Log::info("newcorrsale: Iniciando creación de venta para usuario: " . $iduser . ", tipo documento: " . $iddoc);

            // Obtener la primera empresa con permiso
            $companyId = \DB::table('permission_company')
                ->where('user_id', $iduser)
                ->where('state', 1)
                ->orderBy('id')
                ->value('company_id');

            //\Log::info("newcorrsale: company_id obtenido: " . ($companyId ?: 'NULL'));

            // Validar que se obtuvo un company_id válido
            if (!$companyId) {
                throw new \Exception("No se encontró empresa válida para el usuario ID: " . $iduser);
            }

            // Crear la nueva venta de forma simple y directa
            $corr = new Sale();
            $corr->company_id = $companyId;
            $corr->typedocument_id = $iddoc;
            $corr->user_id = $iduser;
            $corr->date = now();
            $corr->state = 1;
            $corr->typesale = 2; // Borrador de venta

            //\Log::info("newcorrsale: Datos de venta antes de guardar - company_id: " . $companyId . ", typedocument_id: " . $iddoc . ", user_id: " . $iduser);

            $corr->save();

            //\Log::info("newcorrsale: Venta creada exitosamente con ID: " . $corr->id);

            // Verificar que la venta realmente se guardó
            $saleExists = Sale::find($corr->id);
            if (!$saleExists) {
                throw new \Exception("La venta se creó con ID " . $corr->id . " pero no se puede encontrar en la base de datos");
            }

            DB::commit();
            //\Log::info("newcorrsale: Transacción confirmada exitosamente para venta ID: " . $corr->id);

            return response()->json(['sale_id' => $corr->id], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            //\Log::error("newcorrsale: Error creando venta - " . $e->getMessage());
            return response()->json(['error' => 'No se pudo crear la venta', 'message' => $e->getMessage()], 500);
        }
    }


    public function destroysaledetail($idsaledetail)
    {
        $saledetails = Salesdetail::find(base64_decode($idsaledetail));
        $saleId = $saledetails->sale_id;
        $saledetails->delete();

        // Recalcular el total de la venta después de eliminar el producto
        $this->updateSaleTotalAmount($saleId);

        return response()->json(array(
            "res" => "1"
        ));
    }

    public function getdatadocbycorr($corr)
    {
        $decodedCorr = base64_decode($corr);
        //Log::info("getdatadocbycorr: Correlativo recibido: " . $corr);
        //Log::info("getdatadocbycorr: ID decodificado: " . $decodedCorr);

        // Primero verificar si la venta existe
        $sale = Sale::find($decodedCorr);
        if (!$sale) {
            //Log::error("getdatadocbycorr: Venta no encontrada con ID: " . $decodedCorr);
            return response()->json([]);
        }

        //Log::info("getdatadocbycorr: Venta encontrada", [
        //    'sale_id' => $sale->id,
        //    'company_id' => $sale->company_id,
        //    'client_id' => $sale->client_id,
        //    'typedocument_id' => $sale->typedocument_id
        //]);

        // Verificar cada tabla por separado
        $company = \DB::table('companies')->where('id', $sale->company_id)->first();
        //Log::info("getdatadocbycorr: Empresa encontrada: " . ($company ? 'SÍ' : 'NO'));

        $iva = \DB::table('iva')->where('company_id', $sale->company_id)->first();
        //Log::info("getdatadocbycorr: IVA encontrado: " . ($iva ? 'SÍ' : 'NO'));

        $client = null;
        if ($sale->client_id) {
            $client = \DB::table('clients')->where('id', $sale->client_id)->first();
            //Log::info("getdatadocbycorr: Cliente encontrado: " . ($client ? 'SÍ' : 'NO'));
        } else {
            //Log::info("getdatadocbycorr: Sale no tiene client_id asignado");
        }

        // Hacer consulta más robusta con LEFT JOINs
        $saledetails = Sale::leftJoin('companies', 'companies.id', '=', 'sales.company_id')
            ->leftJoin('iva', 'iva.company_id', '=', 'companies.id')
            ->leftJoin('clients', 'clients.id', '=', 'sales.client_id')
            ->select(
                'sales.*',
                'companies.*',
                'companies.id as company_id_override',
                'companies.name as company_name',
                'clients.id AS client_id',
                'clients.firstname AS client_firstname',
                'clients.secondname AS client_secondname',
                'clients.comercial_name AS comercial_name',
                'clients.tipoContribuyente AS client_contribuyente',
                'iva.valor AS iva',
                'iva.valor_entre AS iva_entre'
            )
            ->where('sales.id', '=', $decodedCorr)
            ->get();

        //Log::info("getdatadocbycorr: Resultado de consulta", ['count' => $saledetails->count()]);

        if ($saledetails->isEmpty()) {
            //Log::error("getdatadocbycorr: Consulta devolvió array vacío para venta: " . $decodedCorr);
        } else {
            //Log::info("getdatadocbycorr: Datos encontrados", [
            //    'sale_id' => $saledetails[0]->id ?? 'NULL',
            //    'company_name' => $saledetails[0]->company_name ?? 'NULL',
            //    'client_firstname' => $saledetails[0]->client_firstname ?? 'NULL'
            //]);

            // Formatear la fecha para asegurar que esté en formato Y-m-d
            foreach ($saledetails as $sale) {
                if ($sale->date) {
                    // Asegurar que la fecha esté en formato Y-m-d
                    $sale->date = \Carbon\Carbon::parse($sale->date)->format('Y-m-d');
                }
            }
        }

        return response()->json($saledetails);
    }

    public function updateclient($idsale, $clientid)
    {
        try {
            // Validar que el idsale no esté vacío
            if (!$idsale || $idsale == 'null' || $idsale == '') {
                return response()->json(['error' => 'ID de venta no válido'], 400);
            }

            // Validar que el cliente no esté vacío
            if (!$clientid || $clientid == 'null' || $clientid == '0') {
                return response()->json(['error' => 'Debe seleccionar un cliente válido'], 400);
            }

            // Buscar la venta
            $sale = Sale::find($idsale);
            if (!$sale) {
                return response()->json(['error' => 'No se encontró la venta'], 404);
            }

            // Actualizar el cliente
            $sale->client_id = $clientid;
            $sale->save();

            return response()->json(['success' => true, 'message' => 'Cliente actualizado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar cliente: ' . $e->getMessage()], 500);
        }
    }

    public function getdatadocbycorr2($corr)
    {
        $saledetails = Sale::join('companies', 'companies.id', '=', 'sales.company_id')
            ->join('addresses', 'addresses.id', '=', 'companies.address_id')
            ->join('countries', 'countries.id', '=', 'addresses.country_id')
            ->join('departments', 'departments.id', '=', 'addresses.department_id')
            ->join('municipalities', 'municipalities.id', '=', 'addresses.municipality_id')
            ->join('phones', 'phones.id', '=', 'companies.phone_id')
            ->join('clients', 'clients.id', '=', 'sales.client_id')
            ->join('typedocuments', 'typedocuments.id', '=', 'sales.typedocument_id')
            ->select(
                'sales.*',
                'companies.*',
                'companies.ncr AS NCR',
                'companies.nit AS NIT',
                'countries.name AS country_name',
                'departments.name AS department_name',
                'municipalities.name AS municipality_name',
                'addresses.reference AS address',
                'phones.*',
                'typedocuments.description AS document_name',
                'clients.id AS client_id',
                'clients.firstname AS client_firstname',
                'clients.secondname AS client_secondname',
                'clients.tipoContribuyente AS client_contribuyente',
                'sales.id AS corr',
                'clients.tpersona',
                'clients.name_contribuyente'
            )
            ->where('sales.id', '=', base64_decode($corr))
            ->get();
        return response()->json($saledetails);
    }
//creacion de factura
    public function createdocument($corr, $amount)
    {
        DB::beginTransaction();
        try {
            $amount = substr($amount, 1);
            $salesave = Sale::find(base64_decode($corr));
            $salesave->totalamount = $amount;
            $salesave->typesale = 1; // Cambiar a venta finalizada
            //dd($amount);
            //buscar el correlativo actual
            $newCorr = Correlativo::join('typedocuments as tdoc', 'tdoc.type', '=', 'docs.id_tipo_doc')
                ->where('tdoc.id', '=', $salesave->typedocument_id)
                ->where('docs.id_empresa', '=', $salesave->company_id)
                ->select(
                    'docs.actual',
                    'docs.id'
                )
                ->get();
                //dd($newCorr);
            $salesave->nu_doc = $newCorr[0]->actual;
            $salesave->save();
            //dd($salesave); // Debug comentado

            $idempresa = $salesave->company_id;
            $createdby = $salesave->user_id;
            //$company = Company::find($idempresa);
            //$config = Config::where('company_id', $idempresa)->first();
            //detalle factura
            $detailsbd = Salesdetail::where('sale_id', '=', base64_decode($corr))
                ->select(
                    DB::raw('SUM(nosujeta) nosujeta,
            SUM(exempt) exentas,
            SUM(pricesale) gravadas,
            SUM(nosujeta+exempt+pricesale) subtotalventas,
            0 descnosujeta,
            0 descexenta,
            0 desgravada,
            0 porcedesc,
            0 totaldesc,
            NULL tributos,
            SUM(nosujeta+exempt+pricesale) subtotal,
            SUM(detained) ivarete,
            0 ivarete,
            SUM(renta) rentarete,
            NULL pagos,
            SUM(detained13) iva')
                )
                ->get();
                //dd($detailsbd);
            //detalle de montos de la factura
            $totalPagar = ($detailsbd[0]->nosujeta + $detailsbd[0]->exentas + $detailsbd[0]->gravadas + $detailsbd[0]->iva - ($detailsbd[0]->rentarete + $detailsbd[0]->ivarete));
            $totales = [
                "totalNoSuj" => (float)$detailsbd[0]->nosujeta,
                "totalExenta" => (float)$detailsbd[0]->exentas,
                "totalGravada" => (float)$detailsbd[0]->gravadas,
                "subTotalVentas" => round((float)($detailsbd[0]->subtotalventas), 2),
                "descuNoSuj" => $detailsbd[0]->descnosujeta,
                "descuExenta" => $detailsbd[0]->descexenta,
                "descuGravada" => $detailsbd[0]->desgravada,
                "porcentajeDescuento" => 0.00,
                "totalDescu" => $detailsbd[0]->totaldesc,
                "tributos" =>  null,
                "subTotal" => round((float)($detailsbd[0]->subtotal), 2),
                "ivaPerci1" => 0.00,
                "ivaRete1" => 0.00,
                "reteRenta" => round((float)$detailsbd[0]->rentarete, 2),
                "montoTotalOperacion" => round((float)($detailsbd[0]->subtotal), 2),
                //(float)$encabezado["montoTotalOperacion"],
                "totalNoGravado" => (float)0,
                "totalPagar" => (float)$totalPagar,
                "totalLetras" => numtoletras($totalPagar),
                "saldoFavor" => 0.00,
                "condicionOperacion" => $salesave->waytopay,
                "pagos" => null,
                "totalIva" => (float)$detailsbd[0]->iva
            ];
            //detalle del comprobante como url de firmad∂or y mh etc
            $querydocumento = "SELECT
        a.id id_doc,
        b.`type` id_tipo_doc,
        docs.serie serie,
        docs.inicial inicial,
        docs.final final,
        docs.actual actual,
        docs.estado estado,
        a.company_id id_empresa,
        a.user_id hechopor,
        a.created_at fechacreacion,
        b.description NombreDocumento,
        c.name NombreUsuario,
        c.nit docUser,
        b.codemh tipodocumento,
        b.versionjson versionJson,
        e.url_credencial,
        e.url_envio,
        e.url_invalidacion,
        e.url_contingencia,
        e.url_firmador,
        d.typeTransmission tipogeneracion,
        e.cod ambiente,
        a.updated_at,
        1 aparece_ventas
        FROM sales a
        INNER JOIN typedocuments b ON a.typedocument_id = b.id
        INNER JOIN docs ON b.id = (SELECT t.id FROM typedocuments t WHERE t.type = docs.id_tipo_doc)
        INNER JOIN users c ON a.user_id = c.id
        LEFT JOIN config d ON a.company_id = d.company_id
        LEFT JOIN ambientes e ON d.ambiente = e.id
        WHERE a.id = " . base64_decode($corr) . "";
            $documento = DB::select(DB::raw($querydocumento));
            //dd($documento);

            $queryproducto = "SELECT
        c.id id_producto,
        CASE
        WHEN c.id = 9 THEN CONCAT(c.name, ' ', b.reserva, ' ', b.ruta)
        ELSE c.name
        END AS descripcion,
        b.amountp cantidad,
        b.priceunit precio_unitario,
        0 descuento,
        0 no_imponible,
        (b.pricesale+b.nosujeta+b.exempt) subtotal,
        b.pricesale gravadas,
        b.nosujeta no_sujetas,
        b.exempt exentas,
        b.detained13 iva,
        0 porcentaje_descuento,
        b.detained13 iva_calculado,
        b.renta renta_retenida,
        1 tipo_item,
        59 uniMedida
        FROM sales a
        INNER JOIN salesdetails b ON b.sale_id=a.id
        INNER JOIN products c ON b.product_id=c.id
        WHERE a.id=" . base64_decode($corr) . "";
            $producto = DB::select(DB::raw($queryproducto));
            $detalle = $producto;
            //data del emisor
            $queryemisor = "SELECT
        a.nit,
        a.ncr,
        a.name nombre,
        c.code codActividad,
        c.name descActividad,
        a.name nombreComercial,
        a.tipoEstablecimiento,
        f.code departamento,
        g.code municipio,
        d.reference direccion,
        e.phone telefono,
        NULL codEstableMH,
        NULL codEstable,
        NULL codPuntoVentaMH,
        NULL codPuntoVenta,
        a.email correo,
        b.passkeyPublic clavePublicaMH,
        b.passPrivateKey clavePrivadaMH,
        b.passMH claveApiMH
        FROM companies a
        LEFT JOIN config b ON a.id=b.company_id
        INNER JOIN economicactivities c ON a.economicactivity_id=c.id
        INNER JOIN addresses d ON a.address_id=d.id
        INNER JOIN phones e ON a.phone_id=e.id
        INNER JOIN departments f ON d.department_id=f.id
        INNER JOIN municipalities g ON d.municipality_id=g.id
        WHERE a.id=$idempresa";
            $emisor = DB::select(DB::raw($queryemisor));

            $querycliente = "SELECT
        a.id idcliente,
        a.nit,
        a.ncr,
        CASE
            WHEN a.tpersona = 'N' THEN CONCAT(a.firstname, ' ', a.secondname, ' ' , a.firstlastname, ' ', a.secondlastname)
            WHEN a.tpersona = 'J' THEN CONCAT(a.name_contribuyente)
        END AS nombre,
        b.code codActividad,
        b.name descActividad,
        CASE
            WHEN a.tpersona = 'N' THEN CONCAT(a.firstname, ' ', a.secondname, ' ' , a.firstlastname, ' ', a.secondlastname)
            WHEN a.tpersona = 'J' THEN CONCAT(a.comercial_name)
        END AS nombreComercial,
        a.email correo,
        f.code departamento,
        g.code municipio,
        c.reference direccion,
        p.phone telefono,
        1 id_tipo_contribuyente,
        a.tipoContribuyente id_clasificacion_tributaria,
        0 siempre_retiene,
        36 tipoDocumento,
        a.nit numDocumento,
        36 tipoDocumentoCliente,
        d.code codPais,
        d.name nombrePais,
        0 siempre_retiene_renta
    FROM clients a
    INNER JOIN economicactivities b ON a.economicactivity_id=b.id
    INNER JOIN addresses c ON a.address_id=c.id
    INNER JOIN phones p ON a.phone_id=p.id
    INNER JOIN countries d ON c.country_id=d.id
    INNER JOIN departments f ON c.department_id=f.id
    INNER JOIN municipalities g ON c.municipality_id=g.id
    WHERE a.id = $salesave->client_id";

            // Validar que client_id no esté vacío
            if (empty($salesave->client_id) || $salesave->client_id === null) {
                throw new \Exception('Error: La venta no tiene un cliente asignado. client_id: ' . $salesave->client_id);
            }

            $cliente = DB::select(DB::raw($querycliente));
            //dd($cliente);
            $comprobante = [
                "emisor"    => $emisor,
                "documento" => $documento,
                "detalle"   => $detalle,
                "totales"   => $totales,
                "cliente"   => $cliente
            ];

            if (8 == 7) {
                //dd(json_encode($comprobante));
                $contingencia = [];
                $respuesta_hacienda = [];
                //dd($documento[0]->tipogeneracion);
                if ($documento[0]->tipogeneracion == 1) {
                    //$contingencia = DB::table('Contingencias')->where('idEmpresa', $idempresa)->where('codEstado', '01')->get();
                    $contingencia = 1;
                    if ($contingencia) {
                        $respuesta_hacienda = $this->Enviar_Hacienda($comprobante, "01");
                        //dd($respuesta_hacienda);
                        if ($respuesta_hacienda["codEstado"] == "03") {
                            return json_encode($respuesta_hacienda);
                        }
                        $comprobante["json"] = $respuesta_hacienda;
                    }
                }
                //dd($respuesta_hacienda);
                //create respuesta de MH
                $dtecreate = new Dte();
                $dtecreate->versionJson = $documento[0]->versionJson;
                $dtecreate->ambiente_id = $documento[0]->ambiente;
                $dtecreate->tipoDte = $documento[0]->tipodocumento;
                $dtecreate->tipoModelo = $documento[0]->tipogeneracion;
                $dtecreate->tipoTransmision = 1;
                $dtecreate->tipoContingencia = "null";
                $dtecreate->idContingencia = "null";
                $dtecreate->nameTable = 'Sales';
                $dtecreate->company_id = $idempresa;
                $dtecreate->company_name = $emisor[0]->nombreComercial;
                $dtecreate->id_doc = $respuesta_hacienda["identificacion"]["numeroControl"];
                $dtecreate->codTransaction = "01";
                $dtecreate->desTransaction = "Emision";
                $dtecreate->type_document = $documento[0]->tipodocumento;
                $dtecreate->id_doc_Ref1 = "null";
                $dtecreate->id_doc_Ref2 = "null";
                $dtecreate->type_invalidacion = "null";
                $dtecreate->codEstado = $respuesta_hacienda["codEstado"];
                $dtecreate->Estado = $respuesta_hacienda["estado"];
                $dtecreate->codigoGeneracion = $respuesta_hacienda["codigoGeneracion"];
                $dtecreate->selloRecibido = $respuesta_hacienda["selloRecibido"];
                $dtecreate->fhRecibido = $respuesta_hacienda["fhRecibido"];
                $dtecreate->estadoHacienda = $respuesta_hacienda["estadoHacienda"];
                $dtecreate->json = json_encode($comprobante);
                $dtecreate->nSends = $respuesta_hacienda["nuEnvios"];
                $dtecreate->codeMessage = $respuesta_hacienda["codigoMsg"];
                $dtecreate->claMessage = $respuesta_hacienda["clasificaMsg"];
                $dtecreate->descriptionMessage = $respuesta_hacienda["descripcionMsg"];
                $dtecreate->detailsMessage = $respuesta_hacienda["observacionesMsg"];
                $dtecreate->sale_id = base64_decode($corr);
                $dtecreate->created_by = $documento[0]->NombreUsuario;
                $dtecreate->save();
            }
            //dd($comprobante);
            //update correlativo
            $updateCorr = Correlativo::find($newCorr[0]->id);
            $updateCorr->actual = ($updateCorr->actual + 1);
            $updateCorr->save();
            //if ($dtecreate) $exit = 1;
            $salesave = Sale::find(base64_decode($corr));
            $salesave->json = json_encode($comprobante);
            $salesave->save();
            $exit = 1;
            DB::commit();

                        // Generar ticket automáticamente después de completar la venta
            try {
                $saleId = base64_decode($corr);
                $ticketPrintUrl = route('sale.ticket-print', $saleId);
                $ticketDirectUrl = route('sale.ticket-direct', $saleId);

                // Agregar información del ticket a la respuesta para que el frontend lo abra
                return response()->json(array(
                    "res" => $exit,
                    "ticket_auto" => true,
                    "ticket_print_url" => $ticketPrintUrl,  // Impresión directa del servidor
                    "ticket_url" => $ticketDirectUrl,       // Fallback del navegador
                    "sale_id" => $saleId,
                    "message" => "Venta completada. Generando ticket automáticamente..."
                ));

            } catch (\Exception $e) {
                \Log::error("Error generando ticket automático: " . $e->getMessage());
                // Si falla el ticket, la venta ya está guardada, solo devolver respuesta normal
                return response()->json(array(
                    "res" => $exit,
                    "ticket_error" => "No se pudo generar el ticket automáticamente"
                ));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudo procesar el documento', 'message' => $e->getMessage()], 500);
        }
    }

    public function getdetailsdoc($corr)
    {
        $saledetails = Salesdetail::leftJoin('products', 'products.id', '=', 'salesdetails.product_id')
        ->leftjoin('marcas', 'marcas.id', '=', 'products.marca_id')
            ->select(
                'salesdetails.*',
                DB::raw('CONCAT(products.name, " ", marcas.name  ) as product_name')
            )
            ->where('sale_id', '=', base64_decode($corr))
            ->get();
            //dd($saledetails);
        return response()->json($saledetails);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $typedocument = request()->get('typedocument', 6); // Por defecto Factura
        $draftId = request()->get('draft_id');

        // Determinar el tipo de documento
        switch ($typedocument) {
            case 6:
                $document = 'Factura';
                break;
            case 8:
                $document = 'Factura de sujeto excluido';
                break;
            case 3:
                $document = 'Crédito Fiscal';
                break;
            default:
                $document = 'Factura';
                break;
        }

        return view('sales.create', compact('document', 'typedocument', 'draftId'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Sale  $Sale
     * @return \Illuminate\Http\Response
     */
    public function show(Sale $Sale)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Sale  $Sale
     * @return \Illuminate\Http\Response
     */
    public function edit(Sale $Sale)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Sale  $Sale
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sale $Sale)
    {
        //
    }

    public function ncr($id_sale)
    {

        $id_sale = base64_decode($id_sale);

        $qfactura = "SELECT
                        *,
                        s.id id_factura,
                        s.id numero_factura,
                        s.created_at fecha_factura,
                        s.client_id id_cliente,
                        s.user_id id_vendedor,
                        '1' condiciones,
                        s.totalamount total_venta,
                        s.typesale estado_factura,
                        s.company_id id_empresa,
                        s.user_id hechopor,
                        s.created_at fechacreacion,
                        CASE
                                WHEN clie.tpersona = 'N' THEN CONCAT(clie.firstname, ' ', clie.secondname, ' ' , clie.firstlastname, ' ', clie.secondlastname)
                                WHEN clie.tpersona = 'J' THEN CONCAT(clie.name_contribuyente)
                            END AS nombre_cliente,
                        doct.type tipo_doc,
                        s.acuenta anombrede,
                        (SELECT SUM(sdet.exempt) FROM salesdetails sdet WHERE sdet.sale_id=s.id) exentas,
                        (SELECT SUM(sdet.nosujeta) FROM salesdetails sdet WHERE sdet.sale_id=s.id) nosujetas,
                        0.0 impint,
                        (SELECT SUM(sdet.detained13) FROM salesdetails sdet WHERE sdet.sale_id=s.id) iva,
                        (SELECT SUM(sdet.pricesale) FROM salesdetails sdet WHERE sdet.sale_id=s.id) gravadas,
                        '13' tipodoc,
                        clie.nit,
                        clie.email correo,
                        0.00 exentas_terceros,
                        0.00 nosujetas_terceros,
                        0.00 iva_terceros,
                        0.00 gravadas_terceros,
                        (SELECT SUM(sdet.detained) FROM salesdetails sdet WHERE sdet.sale_id=s.id) iva_retenido,
                        ad.reference direccion_cliente,
                        clie.ncr,
                        clie.nit,
                        clie.email email_cliente,
                        clie.tpersona tipo_personeria,
                        muni.code municipio,
                        dep.code departamento,
                        (SELECT SUM(sdet.pricesale) FROM salesdetails sdet WHERE sdet.sale_id=s.id) total_gravadas,
                        (SELECT SUM(sdet.detained13) FROM salesdetails sdet WHERE sdet.sale_id=s.id) total_iva,
                        (SELECT SUM(sdet.nosujeta) FROM salesdetails sdet WHERE sdet.sale_id=s.id) total_nosujetas,
                        (SELECT SUM(sdet.exempt) FROM salesdetails sdet WHERE sdet.sale_id=s.id) total_exentas,
                        econo.code giro,
                        dte.json,
                        dte.tipoModelo,
                        dte.fhRecibido,
                        am.url_contingencia,
                        am.url_envio,
                        am.url_credencial,
                        am.url_invalidacion,
                        am.url_firmador,
                        dte.nSends nuEnvios,
                        dte.updated_at,
                        am.cod,
                        dte.versionJson,
                        dte.selloRecibido,
                        dte.codigoGeneracion,
                        dte.tipoDte,
                        s.doc_related doc_relacionado,
                        '0000000000' serie,
                        users.name NombreUsuario,
                        users.nit docUser
                        FROM
                        sales s
                        INNER JOIN users ON s.user_id=users.id
                        LEFT JOIN dte ON dte.sale_id=s.id
                        INNER JOIN ambientes am ON CONCAT('0',dte.ambiente_id)=am.cod
                        INNER JOIN clients clie ON s.client_id=clie.id
                        INNER JOIN typedocuments doct ON s.typedocument_id=doct.id
                        INNER JOIN addresses ad ON clie.address_id=ad.id
                        INNER JOIN countries cou ON ad.department_id=cou.id
                        INNER JOIN departments dep ON ad.department_id=dep.id
                        INNER JOIN municipalities muni ON ad.municipality_id=muni.id
                        INNER JOIN economicactivities econo ON clie.economicactivity_id=econo.id
                        WHERE s.id = $id_sale";
        $factura = DB::select(DB::raw($qfactura));
        $qdoc = "SELECT
                a.id id_doc,
                a.`type` id_tipo_doc,
                docs.serie serie,
                docs.inicial inicial,
                docs.final final,
                docs.actual actual,
                docs.estado estado,
                a.company_id id_empresa,
                NULL hechopor,
                a.created_at fechacreacion,
                a.description NombreDocumento,
                NULL NombreUsuario,
                NULL docUser,
                a.codemh tipodocumento,
                a.versionjson versionJson,
                e.url_credencial,
                e.url_envio,
                e.url_invalidacion,
                e.url_contingencia,
                e.url_firmador,
                d.typeTransmission tipogeneracion,
                e.cod ambiente,
                a.updated_at,
                1 aparece_ventas
                FROM typedocuments a
                INNER JOIN docs ON a.id = (SELECT t.id FROM typedocuments t WHERE t.type = docs.id_tipo_doc)
                INNER JOIN config d ON a.company_id=d.company_id
                INNER JOIN ambientes e ON d.ambiente=e.id
                WHERE a.`type`= 'NCR'";
        $doc = DB::select(DB::raw($qdoc));
        $qfacturadet = "SELECT
                        *,
                        det.id id_factura_det,
                        det.sale_id id_factura,
                        det.product_id id_producto,
                        pro.description descripcion,
                        det.amountp cantidad,
                        det.priceunit precio_unitario,
                        det.nosujeta no_sujetas,
                        det.exempt exentas,
                        det.pricesale gravadas,
                        det.detained13 iva,
                        0.00 no_imponible,
                        sa.company_id id_empresa,
                        CASE
                                WHEN pro.`type` = 'tercero' THEN 'T'
                                WHEN pro.`type` = 'directo' THEN 'D'
                            END AS tipo_producto,
                        0.00 porcentaje_descuento,
                        0.00 descuento,
                        det.created_at,
                        det.updated_at
                        FROM salesdetails det
                        INNER JOIN sales sa ON det.sale_id=sa.id
                        INNER JOIN products pro ON det.product_id=pro.id
                        WHERE det.sale_id = $id_sale";
        $detalle = DB::select(DB::raw($qfacturadet));
        $versionJson = $doc[0]->versionJson;
        $ambiente = $doc[0]->ambiente;
        $tipoDte = $doc[0]->tipodocumento;
        $tipoTransmision = $doc[0]->tipogeneracion;
        $tipoContingencia = null;
        $idContingencia = null;
        $codTransacion = '01';
        $codEstado = null;
        $codigoGeneracion = null;
        $selloRecibido = null;
        $fhRecibido = null;
        $codEstadoHacienda = null;
        $estadoHacienda = null;
        $nuEnvios = null;
        $codigoMsg = null;
        $clasificaMSg = null;
        $descripcionMsg = null;
        $observacionesMsg = null;
        $json = $factura[0]->json;

        $numero = $doc[0]->actual;

        $documento[0] = [
            "tipodocumento"         => $doc[0]->tipodocumento,
            "nu_doc"                => $numero,
            "tipo_establecimiento"  => "1",  //Cambiar,
            "version"               => $doc[0]->versionJson,
            "ambiente"              => $doc[0]->ambiente,
            "tipoDteOriginal"           => $factura[0]->tipoDte,
            "tipoGeneracionOriginal"    => $factura[0]->tipoModelo,
            "codigoGeneracionOriginal" => $factura[0]->codigoGeneracion,
            "selloRecibidoOriginal"     => $factura[0]->selloRecibido,
            "numeroOriginal"            => $factura[0]->codigoGeneracion,
            "fecEmiOriginal"            => date('Y-m-d', strtotime($factura[0]->fhRecibido)),
            "total_iva"                 => $factura[0]->iva,
            "tipoDocumento"             => "",
            "numDocumento"              => $factura[0]->nit,
            "nombre"                    => $factura[0]->anombrede,
            "versionjson"               => $doc[0]->versionJson,
            "id_empresa"                => $factura[0]->id_empresa,
            "url_credencial"            => $factura[0]->url_credencial,
            "url_envio"                 => $factura[0]->url_envio,
            "url_firmador"              => $factura[0]->url_firmador,
            "nuEnvio"                   => 1,
            "condiciones"               => $factura[0]->condiciones,
            "total_venta"                => $factura[0]->total_venta,
            "tot_gravado"               => $factura[0]->total_gravadas,
            "tot_nosujeto"              => $factura[0]->total_nosujetas,
            "tot_exento"                => $factura[0]->total_exentas,
            "subTotalVentas"            => $factura[0]->total_gravadas + $factura[0]->total_nosujetas + $factura[0]->total_exentas,
            "descuNoSuj"                => 0.00,
            "descuExenta"               => 0.00,
            "descuGravada"              => 0.00,
            "totalDescu"                => 0.00,
            "subTotal"                  => $factura[0]->total_gravadas + $factura[0]->total_nosujetas + $factura[0]->total_exentas,
            "ivaPerci1"                 => 0.00,
            "ivaRete1"                  => $factura[0]->iva_retenido,
            "reteRenta"                 => 0.00,
            "total_letras"              => numeroletras($factura[0]->total_venta),
            "totalPagar"                => $factura[0]->total_venta,
            "NombreUsuario"             => $factura[0]->NombreUsuario,
            "docUser"                   => $factura[0]->docUser

        ];
        //dd($factura);
        $qcliente = "SELECT
                                a.id id_cliente,
                            CASE
                                WHEN a.tpersona = 'N' THEN CONCAT(a.firstname, ' ', a.secondname, ' ' , a.firstlastname, ' ', a.secondlastname)
                                WHEN a.tpersona = 'J' THEN CONCAT(a.name_contribuyente)
                            END AS nombre_cliente,
                                p.phone telefono_cliente,
                                a.email email_cliente,
                                c.reference direccion_cliente,
                                1 status_cliente,
                                a.created_at date_added,
                                a.ncr,
                            a.nit,
                            a.tpersona tipo_personeria,
                            g.code municipio,
                            f.code departamento,
                            a.company_id id_empresa,
                            NULL hechopor,
                            a.tipoContribuyente id_clasificacion_tributaria,
                            CASE
                                WHEN a.tipoContribuyente = 'GRA' THEN 'GRANDES CONTRIBUYENTES'
                                WHEN a.tipoContribuyente = 'MED' THEN 'MEDIANOS CONTRIBUYENTES'
                                WHEN a.tipoContribuyente = 'PEQU'  THEN 'PEQUEÑOS CONTRIBUYENTES'
                                WHEN a.tipoContribuyente = 'OTR'  THEN 'OTROS CONTRIBUYENTES'
                            END AS descripcion,
                            0 siempre_retiene,
                            1 id_tipo_contribuyente,
                            b.id giro,
                            b.code codActividad,
                            b.name descActividad,
                            a.comercial_name nombre_comercial
                        FROM clients a
                        INNER JOIN economicactivities b ON a.economicactivity_id=b.id
                        INNER JOIN addresses c ON a.address_id=c.id
                        INNER JOIN phones p ON a.phone_id=p.id
                        INNER JOIN countries d ON c.country_id=d.id
                        INNER JOIN departments f ON c.department_id=f.id
                        INNER JOIN municipalities g ON c.municipality_id=g.id
                        WHERE a.id = " . $factura[0]->id_cliente . "";
        $cliente = DB::select(DB::raw($qcliente));

        //data del emisor
        $queryemisor = "SELECT
                        a.nit,
                        a.ncr,
                        a.name nombre,
                        c.code codActividad,
                        c.name descActividad,
                        a.name nombreComercial,
                        a.tipoEstablecimiento,
                        f.code departamento,
                        g.code municipio,
                        d.reference direccion,
                        e.phone telefono,
                        NULL codEstableMH,
                        NULL codEstable,
                        NULL codPuntoVentaMH,
                        NULL codPuntoVenta,
                        a.email correo,
                        b.passkeyPublic clavePublicaMH,
                        b.passPrivateKey clavePrivadaMH,
                        b.passMH claveApiMH
                        FROM companies a
                        INNER JOIN config b ON a.id=b.company_id
                        INNER JOIN economicactivities c ON a.economicactivity_id=c.id
                        INNER JOIN addresses d ON a.address_id=d.id
                        INNER JOIN phones e ON a.phone_id=e.id
                        INNER JOIN departments f ON d.department_id=f.id
                        INNER JOIN municipalities g ON d.municipality_id=g.id
                        WHERE a.id=" . $factura[0]->id_empresa . "";
        $emisor = DB::select(DB::raw($queryemisor));
        $comprobante = [
            "emisor" => $emisor,
            "documento" => $documento,
            "detalle" => $detalle,
            "totales" => $detalle,
            "cliente" => $cliente
        ];
        //dd();
        //dd($factura);
        $respuesta = $this->Enviar_Hacienda($comprobante, "05");
        if ($respuesta["codEstado"] == "03") {
            return json_encode($respuesta);
        }
        $comprobante["json"] = $respuesta;

        $codEstado = $respuesta["codEstado"];
        $codigoGeneracion = $respuesta["codigoGeneracion"];
        $selloRecibido = $respuesta["selloRecibido"];
        $fhRecibido = $respuesta["fhRecibido"];
        $codEstadoHacienda = null;
        $estadoHacienda = $respuesta["estadoHacienda"];
        $nuEnvios = 1;
        $codigoMsg = $respuesta["codigoMsg"];
        $clasificaMSg = $respuesta["clasificaMsg"];
        $descripcionMsg = $respuesta["descripcionMsg"];
        $observacionesMsg = $respuesta["observacionesMsg"];

        //dd($factura);
        $newCorr = Correlativo::join('typedocuments as tdoc', 'tdoc.type', '=', 'docs.id_tipo_doc')
            ->where('tdoc.id', '=', '9')
            ->where('docs.id_empresa', '=', $factura[0]->company_id)
            ->select(
                'docs.actual',
                'docs.id'
            )
            ->get();
        $nfactura = new Sale();
        $nfactura->acuenta = $factura[0]->anombrede;
        $nfactura->nu_doc = $newCorr[0]->actual;
        $nfactura->state =  1;
        $nfactura->state_credit = $factura[0]->state_credit;
        $nfactura->totalamount = $factura[0]->totalamount;
        $nfactura->waytopay = $factura[0]->waytopay;
        $nfactura->typesale = 1;
        $nfactura->date = $factura[0]->date;
        $nfactura->user_id = $factura[0]->user_id;
        $nfactura->typedocument_id = 9;
        $nfactura->client_id = $factura[0]->client_id;
        $nfactura->company_id = $factura[0]->company_id;
        $nfactura->doc_related = $factura[0]->id_factura;
        $nfactura->save();

        foreach ($detalle as $d) {
            $n = new Salesdetail();
            $n->sale_id = $nfactura["id"];
            $n->product_id = $d->product_id;
            $n->amountp = $d->amountp;
            $n->pricesale = $d->pricesale;
            $n->priceunit = $d->priceunit;
            $n->nosujeta = $d->nosujeta;
            $n->exempt = $d->exempt;
            $n->detained = $d->detained;
            $n->detained13 = $d->detained13;
            $n->save();
        }
        //dd($respuesta);
        $dtecreate = new Dte();
        $dtecreate->versionJson = $documento[0]["versionjson"];
        $dtecreate->ambiente_id = $documento[0]["ambiente"];
        $dtecreate->tipoDte = $documento[0]["tipodocumento"];
        $dtecreate->tipoModelo = 2;
        $dtecreate->tipoTransmision = 1;
        $dtecreate->tipoContingencia = "null";
        $dtecreate->idContingencia = "null";
        $dtecreate->nameTable = 'Sales';
        $dtecreate->company_id = $factura[0]->id_empresa;
        $dtecreate->company_name = $emisor[0]->nombreComercial;
        $dtecreate->id_doc = $respuesta["identificacion"]["numeroControl"];
        $dtecreate->codTransaction = "01";
        $dtecreate->desTransaction = "Emision";
        $dtecreate->type_document = $documento[0]["tipodocumento"];
        $dtecreate->id_doc_Ref1 = "null";
        $dtecreate->id_doc_Ref2 = $factura[0]->id_factura;
        $dtecreate->type_invalidacion = "null";
        $dtecreate->codEstado = $respuesta["codEstado"];
        $dtecreate->Estado = $respuesta["estado"];
        $dtecreate->codigoGeneracion = $respuesta["codigoGeneracion"];
        $dtecreate->selloRecibido = $respuesta["selloRecibido"];
        $dtecreate->fhRecibido = $respuesta["fhRecibido"];
        $dtecreate->estadoHacienda = $respuesta["estadoHacienda"];
        $dtecreate->json = json_encode($comprobante);;
        $dtecreate->nSends = $respuesta["nuEnvios"];
        $dtecreate->codeMessage = $respuesta["codigoMsg"];
        $dtecreate->claMessage = $respuesta["clasificaMsg"];
        $dtecreate->descriptionMessage = $respuesta["descripcionMsg"];
        $dtecreate->detailsMessage = $respuesta["observacionesMsg"];
        $dtecreate->sale_id = $nfactura["id"];
        $dtecreate->created_by = $documento[0]["NombreUsuario"];
        $dtecreate->save();

        $updateCorr = Correlativo::find($newCorr[0]->id);
        $updateCorr->actual = ($updateCorr->actual + 1);
        $updateCorr->save();

        if ($dtecreate) $exit = 1;
        else $exit = 0;

        return response()->json(array(
            "res" => $exit
        ));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Sale  $Sale
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $idFactura = base64_decode($id);
        $anular = Sale::find($idFactura);
        $anular->state = 0;
        $anular->typesale = 0;

        $queryinvalidacion = "SELECT
        b.tipoModelo,
        b.type_document,
        b.sale_id numero_factura,
        b.id_doc,
        b.tipoDte,
        am.cod ambiente,
        comp.tipoEstablecimiento,
        b.codigoGeneracion,
        b.selloRecibido,
        b.fhRecibido,
        (SELECT SUM(det.detained13) FROM salesdetails det WHERE det.sale_id=a.id) iva,
        clie.nit,
        CASE
                WHEN clie.tpersona = 'N' THEN CONCAT(clie.firstname, ' ', clie.secondname, ' ' , clie.firstlastname, ' ', clie.secondlastname)
                WHEN clie.tpersona = 'J' THEN CONCAT(clie.name_contribuyente)
            END AS anombrede,
        a.company_id id_empresa,
        a.client_id id_cliente,
        am.url_credencial,
        am.url_invalidacion,
        am.url_firmador
        FROM sales a
        INNER JOIN clients clie ON a.client_id=clie.id
        INNER JOIN companies comp ON a.company_id=comp.id
        INNER JOIN dte b ON b.sale_id=a.id
        LEFT JOIN ambientes am ON CONCAT('0',b.ambiente_id)=am.cod
        WHERE a.id = $idFactura";
        $invalidacion = DB::select(DB::raw($queryinvalidacion));
        //data del emisor
        $queryemisor = "SELECT
        a.nit,
        a.ncr,
        a.name nombre,
        c.code codActividad,
        c.name descActividad,
        a.name nombreComercial,
        a.tipoEstablecimiento,
        f.code departamento,
        g.code municipio,
        d.reference direccion,
        e.phone telefono,
        NULL codEstableMH,
        NULL codEstable,
        NULL codPuntoVentaMH,
        NULL codPuntoVenta,
        a.email correo,
        b.passkeyPublic clavePublicaMH,
        b.passPrivateKey clavePrivadaMH,
        b.passMH claveApiMH
        FROM companies a
        INNER JOIN config b ON a.id=b.company_id
        INNER JOIN economicactivities c ON a.economicactivity_id=c.id
        INNER JOIN addresses d ON a.address_id=d.id
        INNER JOIN phones e ON a.phone_id=e.id
        INNER JOIN departments f ON d.department_id=f.id
        INNER JOIN municipalities g ON d.municipality_id=g.id
        WHERE a.id=$anular->company_id";
        $emisor = DB::select(DB::raw($queryemisor));


        $queryproducto = "SELECT
        c.id id_producto,
        c.description descripcion,
        b.amountp cantidad,
        b.priceunit precio_unitario,
        0 descuento,
        0 no_imponible,
        (b.pricesale+b.nosujeta+b.exempt) subtotal,
        b.pricesale gravadas,
        b.nosujeta no_sujetas,
        b.exempt exentas,
        b.detained13 iva,
        0 porcentaje_descuento,
        b.detained13 iva_calculado,
        0 renta_retenida,
        1 tipo_item,
        59 uniMedida
        FROM sales a
        INNER JOIN salesdetails b ON b.sale_id=a.id
        INNER JOIN products c ON b.product_id=c.id
        WHERE a.id=$idFactura";
        $producto = DB::select(DB::raw($queryproducto));
        $detalle = $producto;

        $detailsbd = Salesdetail::where('sale_id', '=', $idFactura)
            ->select(
                DB::raw('SUM(nosujeta) nosujeta,
            SUM(exempt) exentas,
            SUM(pricesale) gravadas,
            SUM(nosujeta+exempt+pricesale) subtotalventas,
            0 descnosujeta,
            0 descexenta,
            0 desgravada,
            0 porcedesc,
            0 totaldesc,
            NULL tributos,
            SUM(nosujeta+exempt+pricesale) subtotal,
            SUM(detained) ivarete,
            0 ivarete,
            0 rentarete,
            NULL pagos,
            SUM(detained13) iva')
            )
            ->get();
        //detalle de montos de la factura
        $totalPagar = ($detailsbd[0]->nosujeta + $detailsbd[0]->exentas + $detailsbd[0]->gravadas + $detailsbd[0]->iva - $detailsbd[0]->ivarete);
        $totales = [
            "totalNoSuj" => (float)$detailsbd[0]->nosujeta,
            "totalExenta" => (float)$detailsbd[0]->exentas,
            "totalGravada" => (float)$detailsbd[0]->gravadas,
            "subTotalVentas" => round((float)($detailsbd[0]->subtotalventas), 2),
            "descuNoSuj" => $detailsbd[0]->descnosujeta,
            "descuExenta" => $detailsbd[0]->descexenta,
            "descuGravada" => $detailsbd[0]->desgravada,
            "porcentajeDescuento" => 0.00,
            "totalDescu" => $detailsbd[0]->totaldesc,
            "tributos" =>  null,
            "subTotal" => round((float)($detailsbd[0]->subtotal), 2),
            "ivaPerci1" => 0.00,
            "ivaRete1" => 0.00,
            "reteRenta" => round((float)$detailsbd[0]->rentarete, 2),
            "montoTotalOperacion" => round((float)($detailsbd[0]->subtotal), 2),
            //(float)$encabezado["montoTotalOperacion"],
            "totalNoGravado" => (float)0,
            "totalPagar" => (float)$totalPagar,
            "totalLetras" => numtoletras($totalPagar),
            "saldoFavor" => 0.00,
            "condicionOperacion" => $anular->waytopay,
            "pagos" => null,
            "totalIva" => (float)$detailsbd[0]->iva
        ];
        $querycliente = "SELECT
        a.id idcliente,
        a.nit,
        a.ncr,
        CASE
            WHEN a.tpersona = 'N' THEN CONCAT(a.firstname, ' ', a.secondname, ' ' , a.firstlastname, ' ', a.secondlastname)
            WHEN a.tpersona = 'J' THEN CONCAT(a.name_contribuyente)
        END AS nombre,
        b.code codActividad,
        b.name descActividad,
        CASE
            WHEN a.tpersona = 'N' THEN CONCAT(a.firstname, ' ', a.secondname, ' ' , a.firstlastname, ' ', a.secondlastname)
            WHEN a.tpersona = 'J' THEN CONCAT(a.comercial_name)
        END AS nombreComercial,
        a.email correo,
        f.code departamento,
        g.code municipio,
        c.reference direccion,
        p.phone telefono,
        1 id_tipo_contribuyente,
        a.tipoContribuyente id_clasificacion_tributaria,
        0 siempre_retiene,
        36 tipoDocumento,
        a.nit numDocumento,
        36 tipoDocumentoCliente,
        d.code codPais,
        d.name nombrePais,
        0 siempre_retiene_renta
    FROM clients a
    INNER JOIN economicactivities b ON a.economicactivity_id=b.id
    INNER JOIN addresses c ON a.address_id=c.id
    INNER JOIN phones p ON a.phone_id=p.id
    INNER JOIN countries d ON c.country_id=d.id
    INNER JOIN departments f ON c.department_id=f.id
    INNER JOIN municipalities g ON c.municipality_id=g.id
    WHERE a.id = $anular->client_id";
        $cliente = DB::select(DB::raw($querycliente));

        $documento[0] = [
            "tipodocumento"         => 99,
            "nu_doc"                => $invalidacion[0]->numero_factura,
            "tipoDteOriginal"       => $invalidacion[0]->tipoDte,
            "tipo_establecimiento"  => $invalidacion[0]->tipoEstablecimiento,  //Cambiar,
            "version"               => 2,
            "ambiente"              => $invalidacion[0]->ambiente,
            "id_doc"                => $invalidacion[0]->id_doc,
            "fecAnulado"            => date('Y-m-d'), //"2022-07-20", // $encabezado["fecEmi"],    //Cambiar
            "horAnulado"            => date("H:i:s"),
            "codigoGeneracionOriginal" => $invalidacion[0]->codigoGeneracion,
            "selloRecibidoOriginal"     => $invalidacion[0]->selloRecibido,
            "fecEmiOriginal"            => date('Y-m-d', strtotime($invalidacion[0]->fhRecibido)),
            "total_iva"                 => $invalidacion[0]->iva,
            "tipoDocumento"             => $invalidacion[0]->type_document,
            "numDocumento"              => $invalidacion[0]->nit,
            "nombre"                    => $invalidacion[0]->anombrede,
            "versionjson"               => 2,
            "id_empresa"                => $invalidacion[0]->id_empresa,
            "url_credencial"            => $invalidacion[0]->url_credencial,
            "url_envio"                 => $invalidacion[0]->url_invalidacion,
            "url_firmador"              => $invalidacion[0]->url_firmador,
            "nuEnvio"                   => 1
        ];
        $comprobante = [
            "emisor"    => $emisor,
            "documento" => $documento,
            "detalle"   => $detalle,
            "totales"   => $totales,
            "cliente"   => $cliente
        ];
        //$cliente = Client::where('id', $invalidacion[0]->id_cliente)->get();
        //dd($documento);
        $respuesta = $this->Enviar_Hacienda($comprobante, "02");
        if ($respuesta["codEstado"] == "03") {
            return json_encode($respuesta);
        }
        $comprobante["json"] = $respuesta;


        //dd($respuesta);
        $dtecreate = new Dte();
        $dtecreate->versionJson = $documento[0]["versionjson"];
        $dtecreate->ambiente_id = $documento[0]["ambiente"];
        $dtecreate->tipoDte = $documento[0]["tipoDocumento"];
        $dtecreate->tipoModelo = 2;
        $dtecreate->tipoTransmision = $documento[0]["tipoDocumento"];
        $dtecreate->tipoContingencia = "null";
        $dtecreate->idContingencia = "null";
        $dtecreate->nameTable = 'Sales';
        $dtecreate->company_id = $anular->company_id;
        $dtecreate->company_name = $emisor[0]->nombreComercial;
        $dtecreate->id_doc = $documento[0]["id_doc"];
        $dtecreate->codTransaction = "02";
        $dtecreate->desTransaction = "Invalidacion";
        $dtecreate->type_document = $documento[0]["tipoDocumento"];
        $dtecreate->id_doc_Ref1 = $documento[0]["id_doc"];
        $dtecreate->id_doc_Ref2 = "null";
        $dtecreate->type_invalidacion = "1";
        $dtecreate->codEstado = $respuesta["codEstado"];
        $dtecreate->Estado = $respuesta["estado"];
        $dtecreate->codigoGeneracion = $respuesta["codigoGeneracion"];
        $dtecreate->selloRecibido = $respuesta["selloRecibido"];
        $dtecreate->fhRecibido = $respuesta["fhRecibido"];
        $dtecreate->estadoHacienda = $respuesta["estadoHacienda"];
        $dtecreate->json = json_encode($comprobante);;
        $dtecreate->nSends = $respuesta["nuEnvios"];
        $dtecreate->codeMessage = $respuesta["codigoMsg"];
        $dtecreate->claMessage = $respuesta["clasificaMsg"];
        $dtecreate->descriptionMessage = $respuesta["descripcionMsg"];
        $dtecreate->detailsMessage = $respuesta["observacionesMsg"];
        $dtecreate->sale_id = $idFactura;
        $dtecreate->created_by = $documento[0]["nombre"];
        $dtecreate->save();
        $anular->save();

        if ($dtecreate && $anular) $exit = 1;
        else $exit = 0;
        return response()->json(array(
            "res" => $exit
        ));
    }

    public function Enviar_Hacienda($comprobante, $codTransaccion = "01")
    {
        //$codTransaccion ='01';
        date_default_timezone_set('America/El_Salvador');
        ini_set('max_execution_time', '300');
        $respuesta = [];
        $comprobante_electronico = [];
        //return $comprobante_electronico;
        $comprobante_electronico = convertir_json($comprobante, $codTransaccion);
        //return $comprobante_electronico;
        if ($codTransaccion == "02" || $codTransaccion == "05") {
            $tipo_documento = $comprobante["documento"][0]["tipodocumento"];
        } else {
            $tipo_documento = $comprobante["documento"][0]->tipodocumento;
        }
        //$tipo_documento = $comprobante["documento"][0]->tipodocumento;
        //dd($comprobante);
        if ($codTransaccion == "02" || $codTransaccion == "05") {
            $version = $comprobante["documento"][0]["version"];
        } else {
            $version = $comprobante["documento"][0]->versionJson;
        }
        //$version = $comprobante["documento"][0]->versionJson;
        if ($codTransaccion == '01' || $codTransaccion == "05") {
            $numero_control = $comprobante_electronico["identificacion"]["numeroControl"];
        } else {
            $numero_control = 'Anulacion o Contingencia';
        }
        $empresa = $comprobante["documento"][0];
        $id_empresa = ($codTransaccion == "02" || $codTransaccion == "05" ? $comprobante["documento"][0]["id_empresa"] : $empresa->id_empresa);
        $ambiente = ($codTransaccion == "02" || $codTransaccion == "05" ? $comprobante["documento"][0]["ambiente"] : $empresa->ambiente);
        $emisor = $comprobante["emisor"];
        $url_credencial = ($codTransaccion == "02" || $codTransaccion == "05" ? $comprobante["documento"][0]["url_credencial"] : $empresa->url_credencial);
        $url_envio = ($codTransaccion == "02" || $codTransaccion == "05" ? $comprobante["documento"][0]["url_envio"] : $empresa->url_envio);
        $url_firmador = ($codTransaccion == "02" || $codTransaccion == "05" ? $comprobante["documento"][0]["url_firmador"] : $empresa->url_firmador);
        //dd(str_replace('-','',$emisor[0]->nit));
        $firma_electronica = [
            "nit" => str_replace('-', '', $emisor[0]->nit),
            "activo" => true,
            "passwordPri" => $emisor[0]->clavePrivadaMH,
            "dteJson" => $comprobante_electronico
        ];
        //dd($firma_electronica);
        //return json_encode($firma_electronica);
        //dd(json_encode($firma_electronica));
        //dd($url_firmador);
        try {
            $response = Http::accept('application/json')->post($url_firmador, $firma_electronica);
        } catch (\Throwable $th) {
            $error = [
                "mensaje" => "Error en Firma de Documento",
                "error" => $th
            ];
            return  json_encode($error);
        }
        //return "aqui llego";
        //return $response;
        $objResponse = json_decode($response, true);
        //dd($objResponse);
        //return json_last_error_msg();
        $objResponse = (array)$objResponse;
        $comprobante_encriptado = $objResponse["body"];
        $validacion_usuario = [
            "user"  => str_replace('-', '', $emisor[0]->nit),
            "pwd"   => $emisor[0]->claveApiMH
        ];

        //dd($validacion_usuario);
        //dd($this->getTokenMH($id_empresa, $validacion_usuario, $url_credencial, $url_credencial));
        if ($this->getTokenMH($id_empresa, $validacion_usuario, $url_credencial, $url_credencial) == "OK") {
            // return 'paso validacion';
            $token = Session::get($id_empresa);
            //$ambiente = $comprobante["documento"][0]->ambiente;
            //dd($documento[0]);
            //return ["token" => $token];
            //dd($codTransaccion);
            if ($codTransaccion == "01" || $codTransaccion == "05") {
                $comprobante_enviar = [
                    "ambiente"      => $ambiente,
                    "idEnvio"       => 1, //intval($comprobante["nuEnvio"]),
                    "version"       => intval($version),
                    "tipoDte"       => $tipo_documento,
                    "documento"     => $comprobante_encriptado
                ];
            } else {
                $comprobante_enviar = [
                    "ambiente"      => $ambiente,
                    "idEnvio"       => intval($empresa["nuEnvio"]),
                    "version"       => intval($version),
                    "documento"     => $comprobante_encriptado
                ];
            }

            //dd($comprobante_enviar);
            //dd($url_envio);
            try {
                $response_enviado = Http::withToken($token)->post($url_envio, $comprobante_enviar);
            } catch (\Throwable $th) {
                //return 'entro aqui';
                $error  = [
                    "mensaje" => "Error con Servicios de Hacienda",
                    "erro" => $th
                ];
                return json_encode($error);
            }
        } else {
            $response_enviado = $this->getTokenMH($id_empresa, $url_credencial, $url_credencial);
        }

        //dd($comprobante);

        //return json_encode($comprobante);
        //dd($response_enviado);
        $objEnviado = json_decode($response_enviado);
        //dd($objEnviado);
        if (isset($objEnviado->estado)) {
            $estado_envio = $objEnviado->estado;
            $dateString = $objEnviado->fhProcesamiento;
            $myDateTime = DateTime::createFromFormat('d/m/Y H:i:s', $dateString);
            $newDateString = $myDateTime->format('Y-m-d H:i:s');
            //$prueba = gettype($objEnviado->observaciones);
            //dd($objEnviado->observaciones);
            $observaciones = implode("<br>", $objEnviado->observaciones);
            if ($estado_envio == "PROCESADO") {
                $respuesta = [
                    "codEstado"         => "02",
                    "estado"            => "Enviado",
                    "codigoGeneracion"  => $objEnviado->codigoGeneracion,
                    "fhRecibido"        => $newDateString,
                    "selloRecibido"     => $objEnviado->selloRecibido,
                    "estadoHacienda"    => $objEnviado->estado,
                    "nuEnvios"          => 1,
                    "clasificaMsg"      => $objEnviado->clasificaMsg,
                    "codigoMsg"         =>  $objEnviado->codigoMsg,
                    "descripcionMsg"    => $objEnviado->descripcionMsg,
                    "observacionesMsg"  => $observaciones,

                ];
                $comprobante_electronico["selloRecibido"] = $objEnviado->selloRecibido;
                if ($codTransaccion == '01' || $codTransaccion == '05') {
                    if ($tipo_documento == '14') {
                        $respuesta["receptor"] = $comprobante_electronico["sujetoExcluido"];
                    } else {
                        $respuesta["receptor"] = $comprobante_electronico["receptor"];
                    }

                    $respuesta["identificacion"]    = $comprobante_electronico["identificacion"];
                    $respuesta["json_enviado"]      = $comprobante_electronico;
                }

                // $this->envia_correo($comprobante);

            } else {
                $respuesta = [
                    "codEstado" =>  "03",
                    "estado" =>  "Rechazado",
                    "descripcionMsg" =>  $objEnviado->descripcionMsg,
                    "observacionesMsg" =>  $observaciones,
                    "nuEnvios" =>  1
                ];
            }
        } else {
            return var_dump($objEnviado);
        }

        return $respuesta;
    }

    public function getTokenMH($id_empresa, $credenciales, $url_seguridad)
    {
        //dd('entra a gettoken');
        if (!Session::has($id_empresa)) {

            //dd('No encuentra la variable');
            //return ["mensaje" => "llama  getnewtokemh"];
            $respuesta =  $this->getNewTokenMH($id_empresa, $credenciales, $url_seguridad);
        } else {
            $now = new Datetime('now');
            $expira = DateTime::createFromFormat('Y-m-d H:i:s', Session::get($id_empresa . '_fecha'));
            $respuesta = 'OK';
            if ($now > $expira) {
                // dd($expira);
                $respuesta = $this->getNewTokenMH($id_empresa, $credenciales, $url_seguridad);
            }
        }
        //dd(Session::get($id_empresa));
        // return ["mensaje" => "pasa la autorizacion OK estoy en get"];
        if ($respuesta == 'OK') {
            return 'OK';
        } else {
            return $respuesta;
        }
    }

    public function getNewTokenMH($id_empresa, $credenciales, $url_seguridad)
    {


        $response_usuario = Http::asForm()->post($url_seguridad, $credenciales);


        //return ["mensaje" => $response_usuario, 'credenciales' => $credenciales];
        $objValidacion = json_decode($response_usuario, true);

        //dd($objValidacion);
        //return ["mensaje" => "pasa la autorizacion"];
        if ($objValidacion["status"] != 'OK') {
            // return ["mensaje" => "no pasa la autorizacion OK"];
            return $objValidacion["status"];
        } else {
            //dd($objValidacion);
            //return ["mensaje" => "pasa la autorizacion OK"];
            Session::put($id_empresa, str_replace('Bearer ', '', $objValidacion["body"]["token"]));
            $fecha_expira = date("Y-m-d H:i:S", strtotime('+24 hours'));
            Session::put($id_empresa . '_fecha', $fecha_expira);
            return 'OK';
        }
    }

    public function envia_correo(Request $request)
    {
        $id_factura = $request->id_factura;
        $nombre = $request->nombre;
        $numero = $request->numero;
        $comprobante = Sale::join('dte', 'dte.sale_id', '=', 'sales.id')
            ->join('companies', 'companies.id', '=', 'sales.company_id')
            ->join('addresses', 'addresses.id', '=', 'companies.address_id')
            ->join('countries', 'countries.id', '=', 'addresses.country_id')
            ->join('departments', 'departments.id', '=', 'addresses.department_id')
            ->join('municipalities', 'municipalities.id', '=', 'addresses.municipality_id')
            ->join('clients as cli', 'cli.id', '=', 'sales.client_id')
            ->join('addresses as add', 'add.id', '=', 'cli.address_id')
            ->join('countries as cou', 'cou.id', '=', 'add.country_id')
            ->join('departments as dep', 'dep.id', '=', 'add.department_id')
            ->join('municipalities as muni', 'muni.id', '=', 'add.municipality_id')
            ->select(
                'sales.*',
                'dte.json as JsonDTE',
                'dte.codigoGeneracion',
                'countries.name as PaisE',
                'departments.name as DepartamentoE',
                'municipalities.name as MunicipioE',
                'cou.name as PaisR',
                'dep.name as DepartamentoR',
                'muni.name as MunicipioR'
            )
            ->where('sales.id', '=', $id_factura)
            ->get();
        //dd($comprobante);
        $email = $request->email;
        //$email ="briandagoberto20@hotmail.com";
        $pdf = $this->genera_pdf($id_factura);
        $json_root = json_decode($comprobante[0]->JsonDTE);
        $json_enviado = $json_root->json->json_enviado;
        $json = json_encode($json_enviado, JSON_PRETTY_PRINT);
        $archivos = [
            $comprobante[0]->codigoGeneracion . '.pdf' => $pdf->output(),
            $comprobante[0]->codigoGeneracion . '.json' => $json
        ];
        $data = ["nombre" => $json_enviado->receptor->nombre, "numero" => $numero,  "json" => $json_enviado];
        $asunto = "Comprobante de Venta No." . $data["json"]->identificacion->numeroControl . ' de Proveedor: ' . $data["json"]->emisor->nombre;
        $correo = new EnviarCorreo($data);
        $correo->subject($asunto);
        foreach ($archivos as $nombreArchivo => $rutaArchivo) {
            $correo->attachData($rutaArchivo, $nombreArchivo);
        }

        Mail::to($email)->send($correo);
    }

    public function genera_pdf($id)
    {
        $factura = Sale::leftjoin('dte', 'dte.sale_id', '=', 'sales.id')
            ->join('companies', 'companies.id', '=', 'sales.company_id')
            ->join('addresses', 'addresses.id', '=', 'companies.address_id')
            ->join('countries', 'countries.id', '=', 'addresses.country_id')
            ->join('departments', 'departments.id', '=', 'addresses.department_id')
            ->join('municipalities', 'municipalities.id', '=', 'addresses.municipality_id')
            ->join('clients as cli', 'cli.id', '=', 'sales.client_id')
            ->join('addresses as add', 'add.id', '=', 'cli.address_id')
            ->join('countries as cou', 'cou.id', '=', 'add.country_id')
            ->join('departments as dep', 'dep.id', '=', 'add.department_id')
            ->join('municipalities as muni', 'muni.id', '=', 'add.municipality_id')
            ->select(
                'dte.json',
                'countries.name as PaisE',
                'departments.name as DepartamentoE',
                'municipalities.name as MunicipioE',
                'cou.name as PaisR',
                'dep.name as DepartamentoR',
                'muni.name as MunicipioR',
                'typedoc.codemh'
            )
            ->where('sales.id', '=', $id)
            ->get();
        //dd($factura);
        $comprobante = json_decode($factura, true);
        //dd(json_decode($comprobante[0]["json"]));
        $data = json_decode($comprobante[0]["json"], true);
        //print_r($data);
        //dd($data);
        $tipo_comprobante = $data["documento"][0]["tipodocumento"];
        //dd($tipo_comprobante);
        switch ($tipo_comprobante) {
            case '03': //CRF
                $rptComprobante = 'pdf.crf';
                break;
            case '01': //FAC
                $rptComprobante = 'pdf.fac';
                break;
            case '11':  //FEX
                $rptComprobante = 'pdf.fex';
                break;
            case '05':
                $rptComprobante = 'pdf.ncr';
                break;

            default:
                # code...
                break;
        }
        @$fecha = $data["json"]["fhRecibido"];
        @$qr = base64_encode(codigoQR($data["documento"][0]["ambiente"], $data["json"]["codigoGeneracion"], $fecha));
        //return  '<img src="data:image/png;base64,'.$qr .'">';
        $data["codTransaccion"] = "01";
        $data["PaisE"] = $factura[0]['PaisE'];
        $data["DepartamentoE"] = $factura[0]['DepartamentoE'];
        $data["MunicipioE"] = $factura[0]['MunicipioE'];
        $data["PaisR"] = $factura[0]['PaisR'];
        $data["DepartamentoR"] = $factura[0]['DepartamentoR'];
        $data["MunicipioR"] = $factura[0]['MunicipioR'];
        $data["qr"] = $qr;
        $tamaño = "Letter";
        $orientacion = "Portrait";
        $pdf = app('dompdf.wrapper');
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isRemoteEnabled', true);
        //dd(asset('/temp'));
        // $pdf->set_option('tempDir', asset('/temp'));
        //dd($data);
        $pdf->loadHtml(ob_get_clean());
        $pdf->setPaper($tamaño, $orientacion);
        $pdf->getDomPDF()->set_option("enable_php", true);
        $pdf->loadView($rptComprobante, $data);
        //dd($pdf);
        return $pdf;
    }
    public function genera_pdflocal($id)
    {
        try {
            $factura = Sale::leftjoin('dte', 'dte.sale_id', '=', 'sales.id')
                ->leftjoin('companies', 'companies.id', '=', 'sales.company_id')
                ->leftjoin('addresses', 'addresses.id', '=', 'companies.address_id')
                ->leftjoin('countries', 'countries.id', '=', 'addresses.country_id')
                ->leftjoin('departments', 'departments.id', '=', 'addresses.department_id')
                ->leftjoin('municipalities', 'municipalities.id', '=', 'addresses.municipality_id')
                ->leftjoin('clients as cli', 'cli.id', '=', 'sales.client_id')
                ->leftjoin('addresses as add', 'add.id', '=', 'cli.address_id')
                ->leftjoin('countries as cou', 'cou.id', '=', 'add.country_id')
                ->leftjoin('departments as dep', 'dep.id', '=', 'add.department_id')
                ->leftjoin('municipalities as muni', 'muni.id', '=', 'add.municipality_id')
                ->leftjoin('typedocuments as typedoc', 'typedoc.id', '=', 'sales.typedocument_id')
                ->select(
                    'sales.*',
                    'dte.json',
                    'sales.json as jsonlocal',
                    'countries.name as PaisE',
                    'departments.name as DepartamentoE',
                    'municipalities.name as MunicipioE',
                    'cou.name as PaisR',
                    'dep.name as DepartamentoR',
                    'muni.name as MunicipioR',
                    'typedoc.codemh'
                )
                ->where('sales.id', '=', $id)
                ->first();

            if (!$factura) {
                throw new \Exception("Venta con ID $id no encontrada");
            }

            if (!$factura->jsonlocal) {
                throw new \Exception("Datos de factura incompletos para ID $id");
            }
            //dd($factura);
            $data = json_decode($factura->jsonlocal, true);
            //dd($data);

            if (!$data) {
                throw new \Exception("No se pudo decodificar los datos JSON de la factura");
            }

            //print_r($data);
            //dd($data);
            //$tipo_comprobante = $data["documento"][0]["tipodocumento"];
            $tipo_comprobante = $factura->codemh;
        switch ($tipo_comprobante) {
            case '03': //CRF
                $rptComprobante = 'pdf.crflocal';
                break;
            case '01': //FAC
                $rptComprobante = 'pdf.faclocal';
                break;
            case '11':  //FEX
                $rptComprobante = 'pdf.fex';
                break;
            case '05':
                $rptComprobante = 'pdf.ncr';
                break;

            default:
                // Usar faclocal como vista por defecto
                $rptComprobante = 'pdf.faclocal';
                \Log::warning("Tipo de comprobante desconocido: $tipo_comprobante. Usando vista por defecto.");
                break;
        }

        // Verificar que la vista existe
        if (!view()->exists($rptComprobante)) {
            throw new \Exception("Vista PDF no encontrada: $rptComprobante");
        }
            //$fecha = $data["json"]["fhRecibido"];
            //dd($data);
            $fecha = $data['documento'][0]['fechacreacion'] ?? date('Y-m-d');

            // Manejo seguro del QR
            try {
                if (function_exists('codigoQR') && isset($data["documento"][0]["ambiente"]) && isset($data["json"]["codigoGeneracion"])) {
                    $qr = base64_encode(codigoQR($data["documento"][0]["ambiente"], $data["json"]["codigoGeneracion"], $fecha));
                } else {
                    $qr = '';
                }
            } catch (\Exception $e) {
                \Log::warning("Error generando código QR: " . $e->getMessage());
                $qr = '';
            }
            //return  '<img src="data:image/png;base64,'.$qr .'">';
            $data["codTransaccion"] = "01";
            $data["PaisE"] = $factura->PaisE ?? '';
            $data["DepartamentoE"] = $factura->DepartamentoE ?? '';
            $data["MunicipioE"] = $factura->MunicipioE ?? '';
            $data["PaisR"] = $factura->PaisR ?? '';
            $data["DepartamentoR"] = $factura->DepartamentoR ?? '';
            $data["MunicipioR"] = $factura->MunicipioR ?? '';
            $data["qr"] = $qr;
            $tamaño = "Letter";
            $orientacion = "Portrait";
            $pdf = app('dompdf.wrapper');

            // Configuraciones optimizadas para evitar problemas de memoria
            $pdf->set_option('isHtml5ParserEnabled', true);
            $pdf->set_option('isRemoteEnabled', true);
            $pdf->set_option('defaultFont', 'Arial');
            $pdf->set_option('dpi', 96);
            $pdf->set_option('fontHeightRatio', 1.1);
            $pdf->set_option('isPhpEnabled', true);

            // Aumentar límite de memoria temporalmente
            ini_set('memory_limit', '256M');

            $pdf->setPaper($tamaño, $orientacion);
            $pdf->getDomPDF()->set_option("enable_php", true);
            $pdf->loadView($rptComprobante, $data);
            //dd($pdf);
            return $pdf;

        } catch (\Exception $e) {
            \Log::error("Error en genera_pdflocal para ID $id: " . $e->getMessage());
            throw $e; // Re-lanzar la excepción para que la maneje el método print()
        }
    }
    public function print($id)
    {
        try {
            \Log::info("Iniciando generación de PDF para venta ID: $id");

            //$pdf = $this->genera_pdf($id);
            $pdf = $this->genera_pdflocal($id);

            \Log::info("PDF generado exitosamente para venta ID: $id");
            return $pdf->stream('comprobante.pdf');
        } catch (\Exception $e) {
            \Log::error("Error al generar PDF para venta ID $id: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());

            // Si es una petición AJAX, devolver JSON
            if (request()->ajax()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Error al generar el PDF: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }

    public function destinos()
    {
        try {
            $destinos = DB::table('aeropuertos')->get();
            return response()->json($destinos, 200);
        } catch (\Exception $e) {
            // En caso de error de base de datos, retornar array vacío para evitar error 500
            Log::warning('Error al consultar tabla aeropuertos: ' . $e->getMessage());
            return response()->json([], 200);
        }
    }

    public function linea()
    {
        try {
            $lineas = DB::table('lineas')->get();
            return response()->json($lineas);
        } catch (\Exception $e) {
            // En caso de error de base de datos, retornar array vacío para evitar error 500
            Log::warning('Error al consultar tabla lineas: ' . $e->getMessage());
            return response()->json([]);
        }
    }



    /**
     * Actualizar el totalamount de una venta basado en sus detalles
     */
    private function updateSaleTotalAmount($saleId)
    {
        try {
            $sale = Sale::find($saleId);
            if (!$sale) {
                return false;
            }

            // Calcular el total basado en los detalles de venta
            $totals = Salesdetail::where('sale_id', $saleId)
                ->selectRaw('
                    SUM(nosujeta) as nosujeta,
                    SUM(exempt) as exempt,
                    SUM(pricesale) as pricesale,
                    SUM(detained13) as iva,
                    SUM(detained) as ivarete,
                    SUM(renta) as renta
                ')
                ->first();

            // Calcular el total a pagar
            $totalAmount = ($totals->nosujeta + $totals->exempt + $totals->pricesale + $totals->iva) - ($totals->renta + $totals->ivarete);

            // Actualizar el totalamount en la venta
            $sale->totalamount = round($totalAmount, 2);
            $sale->save();

            return true;
        } catch (\Exception $e) {
            Log::error('Error actualizando totalamount para venta ' . $saleId . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener datos de un draft de preventa para cargar en el formulario
     * Si draftId = 0, retorna lista de todos los borradores
     * Si draftId > 0, retorna detalles específicos del borrador
     */
    public function getDraftPreventaData($draftId)
    {
        try {
            // Si draftId es 0, retornar lista de borradores
            if ($draftId == 0) {
                $drafts = Sale::with(['client', 'company', 'typedocument', 'user'])
                    ->where('typesale', '3') // Solo drafts de preventas
                    ->orderBy('created_at', 'desc')
                    ->get();

                return response()->json([
                    'success' => true,
                    'drafts' => $drafts,
                    'count' => $drafts->count()
                ]);
            }

            // Si draftId > 0, retornar detalles específicos
            $draft = Sale::with([
                'client',
                'company',
                'typedocument',
                'user',
                'details.product'
            ])
            ->where('id', $draftId)
            ->where('typesale', '3') // Solo drafts de preventas
            ->first();

            if (!$draft) {
                return response()->json([
                    'success' => false,
                    'message' => 'Draft de preventa no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'draft' => $draft
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar draft de preventa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalcular totales de todas las ventas que tengan problemas con el totalamount
     */
    public function recalculateSalesTotals()
    {
        try {
            // Obtener ventas que tienen detalles pero totalamount incorrecto
            $salesWithIssues = Sale::whereHas('details')
                ->where(function($query) {
                    $query->whereNull('totalamount')
                          ->orWhere('totalamount', 0);
                })
                ->get();

            $updated = 0;
            $errors = 0;

            foreach ($salesWithIssues as $sale) {
                if ($this->updateSaleTotalAmount($sale->id)) {
                    $updated++;
                } else {
                    $errors++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Proceso completado. Ventas actualizadas: {$updated}, Errores: {$errors}",
                'updated' => $updated,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Error recalculando totales de ventas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al recalcular totales: ' . $e->getMessage()
            ], 500);
        }
    }


}
