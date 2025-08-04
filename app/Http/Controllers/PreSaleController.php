<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\Salesdetail;
use App\Models\Product;
use App\Models\Client;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PreSaleController extends Controller
{
    /**
     * Mostrar la vista principal de pre-ventas
     */
    public function index()
    {
        $user = Auth::user();
        $companies = Company::join('permission_company', 'companies.id', '=', 'permission_company.company_id')
            ->where('permission_company.user_id', $user->id)
            ->where('permission_company.state', 1)
            ->select('companies.id', 'companies.name as company_name')
            ->get();
        return view('presales.index', compact('companies'));
    }

        /**
     * Iniciar una nueva sesión de pre-venta
     */
    public function startSession(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'client_id' => 'nullable|exists:clients,id',
            'acuenta' => 'nullable|string|max:255',
            'force_new' => 'nullable|boolean'
        ]);

        $user = Auth::user();
        $now = Carbon::now(config('app.timezone', 'America/El_Salvador'));

        // Primero limpiar automáticamente todas las sesiones expiradas del usuario
        $this->cleanupUserExpiredSessions($user->id);

        // Si se solicita forzar nueva sesión, eliminar cualquier sesión activa
        if ($request->force_new) {
            $this->cancelActiveUserSessions($user->id);
        }

        // Verificar si hay una sesión activa vigente para este usuario
        $activeSession = Sale::where('user_id', $user->id)
                            ->where('typesale', '2') // Borrador
                            ->where('created_at', '>=', $now->copy()->subHours(2)) // Sesiones de las últimas 2 horas
                            ->first();

        if ($activeSession) {
            // Verificar nuevamente si la sesión está expirada (doble verificación)
            $sessionAge = $now->diffInMinutes($activeSession->created_at);
            $isExpired = $sessionAge > 120; // 2 horas

            Log::info('Preventas: Sesión activa encontrada después de limpieza', [
                'user_id' => $user->id,
                'session_id' => $activeSession->id,
                'created_at' => $activeSession->created_at,
                'now' => $now,
                'session_age_minutes' => $sessionAge,
                'is_expired' => $isExpired
            ]);

            if ($isExpired) {
                // Si por alguna razón sigue expirada, eliminarla
                Log::info('Preventas: Eliminando sesión expirada restante', ['session_id' => $activeSession->id]);
                Salesdetail::where('sale_id', $activeSession->id)->delete();
                $activeSession->delete();
            } else {
                // Si la sesión está vigente, preguntar si quiere continuar
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tienes una sesión activa. ¿Deseas continuar con la sesión existente?',
                    'existing_session_id' => $activeSession->id,
                    'session_time' => $activeSession->created_at->diffForHumans($now),
                    'session_age_minutes' => $sessionAge,
                    'is_expired' => false
                ], 409);
            }
        }

        // Crear una nueva venta en estado borrador
        $sale = new Sale();
        $sale->company_id = $request->company_id;
        $sale->client_id = $request->client_id;
        $sale->acuenta = $request->acuenta ?? 'Venta al menudeo';
        $sale->user_id = $user->id;
        $sale->typedocument_id = 6; // Factura por defecto
        $sale->date = $now->format('Y-m-d');
        $sale->state = 1;
        $sale->typesale = '2'; // Borrador
        $sale->waytopay = '1'; // Contado por defecto
        $sale->created_at = $now;
        $sale->updated_at = $now;
        $sale->save();

        Log::info('Preventas: Nueva sesión creada', [
            'user_id' => $user->id,
            'session_id' => $sale->id,
            'created_at' => $sale->created_at
        ]);

        return response()->json([
            'success' => true,
            'sale_id' => $sale->id,
            'message' => 'Sesión de pre-venta iniciada correctamente'
        ]);
    }

    /**
     * Buscar producto por código de barras
     */
    public function searchProduct(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50'
        ]);

        $product = Product::where('code', $request->code)
                         ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'stock' => $product->stock,
                'image' => $product->image ?? 'default.png'
            ]
        ]);
    }

    /**
     * Agregar producto a la pre-venta
     */
    public function addProduct(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0'
        ]);

        DB::beginTransaction();
        try {
            $product = Product::find($request->product_id);
            $sale = Sale::find($request->sale_id);

            // Calcular impuestos
            $price = $request->price;
            $quantity = $request->quantity;
            $total = $price * $quantity;

            // Determinar tipo de venta (gravada, exenta, no sujeta)
            $typeSale = $request->type_sale ?? 'gravada';

            $nosujeta = $typeSale === 'nosujeta' ? $total : 0;
            $exempt = $typeSale === 'exenta' ? $total : 0;
            $pricesale = $typeSale === 'gravada' ? $total : 0;

            // Calcular IVA
            $detained13 = $typeSale === 'gravada' ? ($total * 0.13) : 0;
            $detained = $request->ivarete ?? 0;
            $renta = $request->rentarete ?? 0;

            // Crear detalle de venta
            $saleDetail = new Salesdetail();
            $saleDetail->sale_id = $sale->id;
            $saleDetail->product_id = $product->id;
            $saleDetail->amountp = $quantity;
            $saleDetail->priceunit = $price;
            $saleDetail->pricesale = $pricesale;
            $saleDetail->nosujeta = $nosujeta;
            $saleDetail->exempt = $exempt;
            $saleDetail->detained13 = $detained13;
            $saleDetail->detained = $detained;
            $saleDetail->renta = $renta;
            $saleDetail->user_id = Auth::id();
            $saleDetail->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto agregado correctamente',
                'detail_id' => $saleDetail->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener productos de la pre-venta actual
     */
    public function getSaleDetails(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id'
        ]);

        $details = Salesdetail::with('product')
                             ->where('sale_id', $request->sale_id)
                             ->get();

        $totals = [
            'subtotal' => $details->sum('pricesale'),
            'nosujeta' => $details->sum('nosujeta'),
            'exempt' => $details->sum('exempt'),
            'iva' => $details->sum('detained13'),
            'total' => $details->sum('pricesale') + $details->sum('nosujeta') + $details->sum('exempt') + $details->sum('detained13')
        ];

        return response()->json([
            'success' => true,
            'details' => $details,
            'totals' => $totals
        ]);
    }

    /**
     * Remover producto de la pre-venta
     */
    public function removeProduct(Request $request)
    {
        $request->validate([
            'detail_id' => 'required|exists:salesdetails,id'
        ]);

        DB::beginTransaction();
        try {
            $detail = Salesdetail::find($request->detail_id);
            $product = Product::find($detail->product_id);

            // Restaurar stock
            // $product->stock += $detail->amountp;
            // $product->save();

            // Eliminar detalle
            $detail->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto removido correctamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al remover producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalizar pre-venta y crear factura
     */
    public function finalizeSale(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'client_id' => 'nullable|exists:clients,id',
            'acuenta' => 'nullable|string|max:255',
            'waytopay' => 'required|in:1,2,3',
            'typedocument_id' => 'required|exists:typedocuments,id'
        ]);

        DB::beginTransaction();
        try {
            $sale = Sale::find($request->sale_id);

            // Verificar que tenga productos
            $details = Salesdetail::where('sale_id', $sale->id)->count();
            if ($details === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay productos en la venta'
                ], 400);
            }

            // Calcular totales
            $totals = Salesdetail::where('sale_id', $sale->id)
                               ->selectRaw('
                                   SUM(pricesale) as subtotal,
                                   SUM(nosujeta) as nosujeta,
                                   SUM(exempt) as exempt,
                                   SUM(detained13) as iva
                               ')
                               ->first();

            $totalAmount = $totals->subtotal + $totals->nosujeta + $totals->exempt + $totals->iva;

            // Actualizar venta como BORRADOR DE FACTURA con todos los campos necesarios
            // NOTA: El correlativo se asignará cuando se finalice la factura en el módulo de facturación
            $sale->client_id = $request->client_id;
            $sale->acuenta = $request->acuenta ?? 'Venta al menudeo';
            $sale->waytopay = $request->waytopay;
            $sale->typedocument_id = $request->typedocument_id;
            $sale->totalamount = $totalAmount;
            $sale->nu_doc = null; // Se asignará al finalizar la factura
            $sale->typesale = '3'; // BORRADOR DE FACTURA (nuevo estado)
            $sale->state = 1;      // Activo
            $sale->date = now()->format('Y-m-d');
            $sale->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Borrador de factura creado correctamente. Puede completarlo en el módulo de facturación.',
                'sale_id' => $sale->id,
                'total' => $totalAmount,
                'note' => 'El número de correlativo se asignará al finalizar la factura'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar venta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar pre-venta
     */
    public function cancelSale(Request $request)
    {
        $request->validate([
            'sale_id' => 'nullable|exists:sales,id',
            'force' => 'nullable|boolean'
        ]);

        $user = Auth::user();

        // Si es forzado, cancelar todas las sesiones del usuario
        if ($request->force) {
            $canceledCount = $this->cancelActiveUserSessions($user->id);

            return response()->json([
                'success' => true,
                'message' => "Se cancelaron {$canceledCount} sesiones activas",
                'canceled_count' => $canceledCount
            ]);
        }

        // Si no es forzado, requiere sale_id
        if (!$request->sale_id) {
            return response()->json([
                'success' => false,
                'message' => 'ID de venta requerido'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $sale = Sale::find($request->sale_id);

            // Verificar que la sesión pertenezca al usuario
            if ($sale->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para cancelar esta sesión'
                ], 403);
            }

            $details = Salesdetail::where('sale_id', $sale->id)->get();

            // Restaurar stock de todos los productos
            // foreach ($details as $detail) {
            //     $product = Product::find($detail->product_id);
            //     $product->stock += $detail->amountp;
            //     $product->save();
            // }

            // Eliminar detalles y venta
            $details->each->delete();
            $sale->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pre-venta cancelada correctamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar pre-venta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de pre-ventas del día
     */
    public function getDailyStats()
    {
        $user = Auth::user();
        $today = now()->format('Y-m-d');

        $stats = Sale::where('user_id', $user->id)
                    ->where('date', $today)
                    ->whereIn('typesale', ['1', '3']) // Completadas + Borradores de factura
                    ->selectRaw('
                        COUNT(*) as total_sales,
                        SUM(totalamount) as total_amount,
                        COUNT(CASE WHEN client_id IS NULL THEN 1 END) as menudeo_sales,
                        COUNT(CASE WHEN typesale = "3" THEN 1 END) as draft_invoices
                    ')
                    ->first();

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Imprimir recibo de la venta
     */
    public function printReceipt(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id'
        ]);

        $sale = Sale::with(['details.product', 'client', 'company'])
                   ->find($request->sale_id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada'
            ], 404);
        }

        return view('presales.receipt', compact('sale'));
    }

    /**
     * Obtener lista de clientes para el select
     */
    public function getClients()
    {
        $user = Auth::user();
        $clients = Client::where('company_id', $user->company_id)
                        ->orWhere('user_id', $user->id)
                        ->get(['id', 'name', 'firstname', 'secondname', 'firstlastname', 'secondlastname']);

        $formattedClients = $clients->map(function($client) {
            return [
                'id' => $client->id,
                'name' => $client->name ??
                         trim($client->firstname . ' ' . $client->secondname . ' ' .
                              $client->firstlastname . ' ' . $client->secondlastname)
            ];
        });

        return response()->json([
            'success' => true,
            'clients' => $formattedClients
        ]);
    }

    /**
     * Limpiar sesiones expiradas de un usuario específico
     */
    private function cleanupUserExpiredSessions($userId)
    {
        $now = Carbon::now(config('app.timezone', 'America/El_Salvador'));

        // Buscar sesiones expiradas del usuario (más de 2 horas)
        $expiredSessions = Sale::where('user_id', $userId)
                              ->where('typesale', '2') // Borrador
                              ->where('created_at', '<', $now->copy()->subHours(2))
                              ->get();

        $cleanedCount = 0;
        foreach ($expiredSessions as $session) {
            Log::info('Preventas: Limpiando sesión expirada', [
                'user_id' => $userId,
                'session_id' => $session->id,
                'created_at' => $session->created_at,
                'age_hours' => $now->diffInHours($session->created_at)
            ]);

            // Eliminar detalles de la sesión
            Salesdetail::where('sale_id', $session->id)->delete();
            // Eliminar la sesión
            $session->delete();
            $cleanedCount++;
        }

        if ($cleanedCount > 0) {
            Log::info('Preventas: Sesiones limpiadas', [
                'user_id' => $userId,
                'cleaned_count' => $cleanedCount
            ]);
        }

        return $cleanedCount;
    }

    /**
     * Cancelar todas las sesiones activas de un usuario
     */
    private function cancelActiveUserSessions($userId)
    {
        $activeSessions = Sale::where('user_id', $userId)
                             ->where('typesale', '2') // Borrador
                             ->get();

        $canceledCount = 0;
        foreach ($activeSessions as $session) {
            Log::info('Preventas: Cancelando sesión activa', [
                'user_id' => $userId,
                'session_id' => $session->id
            ]);

            // Eliminar detalles de la sesión
            Salesdetail::where('sale_id', $session->id)->delete();
            // Eliminar la sesión
            $session->delete();
            $canceledCount++;
        }

        if ($canceledCount > 0) {
            Log::info('Preventas: Sesiones activas canceladas', [
                'user_id' => $userId,
                'canceled_count' => $canceledCount
            ]);
        }

        return $canceledCount;
    }

    /**
     * Limpiar sesiones expiradas (se ejecuta automáticamente)
     */
    public function cleanupExpiredSessions()
    {
        // Limpiar sesiones de pre-venta que tienen más de 2 horas (unificado)
        $expiredSessions = Sale::where('typesale', '2') // Borrador
                              ->where('created_at', '<', now()->subHours(2))
                              ->get();

        $cleanedCount = 0;
        foreach ($expiredSessions as $session) {
            // Eliminar detalles de la sesión
            Salesdetail::where('sale_id', $session->id)->delete();
            // Eliminar la sesión
            $session->delete();
            $cleanedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Se limpiaron {$cleanedCount} sesiones expiradas",
            'cleaned_count' => $cleanedCount
        ]);
    }

    public function getSessionInfo(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sales,id'
        ]);

        $session = Sale::with(['client', 'user'])
                      ->where('id', $request->session_id)
                      ->where('typesale', '2')
                      ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión no encontrada'
            ], 404);
        }

        $now = Carbon::now(config('app.timezone', 'America/El_Salvador'));
        $sessionAge = $now->diffInMinutes($session->created_at);
        $isExpired = $sessionAge > 120; // 2 horas

        // Si la sesión está expirada, eliminarla automáticamente
        if ($isExpired) {
            Log::info('Preventas: Sesión expirada detectada en getSessionInfo, eliminando', [
                'session_id' => $session->id,
                'session_age_minutes' => $sessionAge
            ]);

            // Eliminar detalles y sesión
            Salesdetail::where('sale_id', $session->id)->delete();
            $session->delete();

            return response()->json([
                'success' => false,
                'message' => 'La sesión ha expirado y fue eliminada automáticamente',
                'is_expired' => true
            ], 404);
        }

        Log::info('Preventas: getSessionInfo', [
            'session_id' => $session->id,
            'created_at' => $session->created_at,
            'now' => $now,
            'sessionAge' => $sessionAge,
            'isExpired' => $isExpired
        ]);

        return response()->json([
            'success' => true,
            'session' => $session,
            'session_age_minutes' => $sessionAge,
            'is_expired' => $isExpired,
            'expires_in_minutes' => max(0, 120 - $sessionAge),
            'created_at_formatted' => $session->created_at->format('d/m/Y H:i:s')
        ]);
    }
}
