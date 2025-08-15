<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Company;
use App\Services\PurchaseInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $purchase = Purchase::join("typedocuments", "typedocuments.id", "=", "purchases.document_id")
        ->join("providers", "providers.id", "=", "purchases.provider_id")
        ->join("companies", "companies.id", "=", "purchases.company_id")
        ->select("purchases.id AS idpurchase",
            "typedocuments.description AS namedoc",
            "purchases.number",
            "purchases.date",
            "purchases.exenta",
            "purchases.gravada",
            "purchases.iva",
            "purchases.otros",
            "purchases.total",
            "providers.razonsocial AS name_provider")
        ->get();
        return view('purchases.index', array(
            "purchases" => $purchase
        ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'document' => 'required',
            'provider' => 'required|exists:providers,id',
            'company' => 'required|exists:companies,id',
            'number' => 'required|string',
            'date' => 'required|date',
            'period' => 'required|string',
            'iduser' => 'required|exists:users,id',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity' => 'required|integer|min:1',
            'details.*.unit_price' => 'required|numeric|min:0',
            'details.*.expiration_date' => 'nullable|date|after_or_equal:date',
            'details.*.batch_number' => 'nullable|string|max:255',
            'details.*.notes' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            // Crear la compra
            $purchase = new Purchase();
            $purchase->document_id = $request->document;
            $purchase->provider_id = $request->provider;
            $purchase->company_id = $request->company;
            $purchase->number = $request->number;
            $purchase->date = $request->date;
            $purchase->fingreso = date('Y-m-d');
            $purchase->periodo = $request->period;
            $purchase->user_id = $request->iduser;
            $purchase->save();

            // Crear los detalles
            $totalExenta = 0;
            $totalGravada = 0;
            $totalIva = 0;
            $totalAmount = 0;

            foreach ($request->details as $detailData) {
                $subtotal = $detailData['quantity'] * $detailData['unit_price'];
                $taxAmount = $subtotal * 0.13; // IVA 13%
                $totalDetail = $subtotal + $taxAmount;

                $detail = PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $detailData['product_id'],
                    'quantity' => $detailData['quantity'],
                    'unit_price' => $detailData['unit_price'],
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalDetail,
                    'expiration_date' => $detailData['expiration_date'] ?? null,
                    'batch_number' => $detailData['batch_number'] ?? null,
                    'notes' => $detailData['notes'] ?? null,
                    'user_id' => $request->iduser
                ]);

                // Actualizar fechas de caducidad si el producto tiene configuración
                $product = Product::find($detailData['product_id']);
                if ($product && $product->hasExpirationConfigured() && !$detail->expiration_date) {
                    $expirationDate = $product->calculateExpirationDate($purchase->date);
                    $detail->update(['expiration_date' => $expirationDate]);
                }

                // Generar número de lote si no se proporcionó
                if (!$detail->batch_number) {
                    $batchNumber = sprintf(
                        'LOT-%s-%s-%s',
                        $purchase->date->format('Ymd'),
                        $product->code ?? $product->id,
                        str_pad($detail->id, 4, '0', STR_PAD_LEFT)
                    );
                    $detail->update(['batch_number' => $batchNumber]);
                }

                $totalExenta += $subtotal;
                $totalGravada += $subtotal;
                $totalIva += $taxAmount;
                $totalAmount += $totalDetail;
            }

            // Actualizar totales de la compra
            $purchase->update([
                'exenta' => $totalExenta,
                'gravada' => $totalGravada,
                'iva' => $totalIva,
                'total' => $totalAmount
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compra creada correctamente',
                'purchase_id' => $purchase->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la compra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getpurchaseid($id){
        $purchase = Purchase::find(base64_decode($id));
        return response()->json($purchase);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function show(Purchase $purchase)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function edit(Purchase $purchase)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Purchase $purchase)
    {
        $purchase = Purchase::findOrFail($request->idedit);
        $purchase->document_id = $request->documentedit;
        $purchase->provider_id = $request->provideredit;
        $purchase->company_id = $request->companyedit;
        $purchase->number = $request->numberedit;
        $daterequest = strtotime($request->dateedit);
        $new_date = date('Y-m-d', $daterequest);
        $purchase->date = $new_date;
        $purchase->exenta = $request->exentaedit;
        $purchase->gravada = $request->gravadaedit;
        $purchase->iva = $request->ivaedit;
        $purchase->contrns = $request->contransedit;
        $purchase->fovial = $request->fovialedit;
        $purchase->iretenido = $request->iretenidoedit;
        $purchase->otros = $request->othersedit;
        $purchase->total = $request->totaledit;
        $purchase->periodo = $request->periodedit;
        $purchase->save();
        return redirect()->route('purchase.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $purchase = Purchase::findOrFail(base64_decode($id));

            // Remover productos del inventario si fueron agregados
            $service = new PurchaseInventoryService();
            $service->removePurchaseFromInventory($purchase);

            // Eliminar la compra (los detalles se eliminan automáticamente por la relación)
            $purchase->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compra eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la compra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar productos de una compra al inventario
     */
    public function addToInventory($id)
    {
        try {
            $purchase = Purchase::findOrFail(base64_decode($id));
            $service = new PurchaseInventoryService();

            $result = $service->addPurchaseToInventory($purchase);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar al inventario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalles de una compra
     */
    public function getDetails($id)
    {
        try {
            $purchase = Purchase::with(['details.product', 'details.product.provider'])
                ->findOrFail(base64_decode($id));

            return response()->json([
                'success' => true,
                'purchase' => $purchase,
                'details' => $purchase->details
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener productos para el formulario de compras
     */
    public function getProducts()
    {
        try {
            $products = Product::with(['provider', 'marca'])
                ->where('state', true)
                ->get();

            return response()->json([
                'success' => true,
                'products' => $products
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener productos próximos a vencer
     */
    public function getExpiringProducts()
    {
        try {
            $service = new PurchaseInventoryService();
            $expiringProducts = $service->checkExpiringProducts();

            return response()->json([
                'success' => true,
                'data' => $expiringProducts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos próximos a vencer: ' . $e->getMessage()
            ], 500);
        }
    }

        /**
     * Obtener productos vencidos
     */
    public function getExpiredProducts()
    {
        try {
            $service = new PurchaseInventoryService();
            $expiredProducts = $service->getExpiredProducts();

            return response()->json([
                'success' => true,
                'data' => $expiredProducts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos vencidos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar vista de productos próximos a vencer
     */
    public function expiringProductsView()
    {
        return view('purchases.expiring-products');
    }
}
