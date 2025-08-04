<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Schema;

class InventoryController extends Controller
{
    public function index()
    {
        $products = Product::with(['provider', 'inventory'])->get();
        return view('inventory.index', compact('products'));
    }

    public function getProviders()
    {
        $providers = Provider::select('id', 'razonsocial')->where('state', 'activo')->get();
        return response()->json($providers);
    }

    public function getinventoryid($id)
    {
        $inventory = Product::join('providers', 'products.provider_id', '=', 'providers.id')
            ->select('products.id as inventoryid', DB::raw('products.name as inventoryname'), 'products.*')
            ->where('products.id', '=', base64_decode($id))
            ->get();
        return response()->json($inventory);
    }

    public function getinventorycode($code)
    {
        $inventory = Product::join('providers', 'products.provider_id', '=', 'providers.id')
            ->select('products.id as inventoryid', DB::raw('products.name as inventoryname'), 'products.*', 'providers.razonsocial as provider')
            ->where('products.code', '=', base64_decode($code))
            ->get();
        return response()->json($inventory);
    }

    public function getinventoryall()
    {
        $inventory = Product::join('providers', 'products.provider_id', '=', 'providers.id')
            ->select('providers.razonsocial as nameprovider', 'providers.id as idprovider', 'products.*')
            ->get();
        return response()->json($inventory);
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
            'productid' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'location' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($request->productid);

            // Verificar si ya existe inventario para este producto
            $existingInventory = Inventory::where('product_id', $request->productid)->first();

            if ($existingInventory) {
                return response()->json(['message' => 'Este producto ya tiene inventario registrado. Use la función de editar para modificar el inventario existente.'], 400);
            }

            // Crear registro de inventario usando create() para manejar automáticamente los campos
            $inventoryData = [
                'product_id' => $request->productid,
                'quantity' => $request->quantity,
                'minimum_stock' => $request->minimum_stock,
                'location' => $request->location
            ];

            // Si la tabla tiene campos adicionales, agregarlos con valores por defecto
            if (Schema::hasColumn('inventory', 'sku')) {
                $inventoryData['sku'] = 'SKU-' . $request->productid . '-' . time();
            }
            if (Schema::hasColumn('inventory', 'name')) {
                $inventoryData['name'] = $product->name;
            }
            if (Schema::hasColumn('inventory', 'description')) {
                $inventoryData['description'] = $product->description;
            }
            if (Schema::hasColumn('inventory', 'price')) {
                $inventoryData['price'] = $product->price;
            }
            if (Schema::hasColumn('inventory', 'category')) {
                $inventoryData['category'] = $product->type;
            }
            if (Schema::hasColumn('inventory', 'user_id')) {
                $inventoryData['user_id'] = auth()->id();
            }
            if (Schema::hasColumn('inventory', 'provider_id')) {
                $inventoryData['provider_id'] = $product->provider_id;
            }
            if (Schema::hasColumn('inventory', 'active')) {
                $inventoryData['active'] = true;
            }

            $inventory = Inventory::create($inventoryData);

            DB::commit();
            return response()->json(['message' => 'Inventario creado correctamente para el producto: ' . $product->name]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear el inventario: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $inventory = Inventory::find($id);

            if (!$inventory) {
                return response()->json(['message' => 'No se encontró inventario para este producto'], 404);
            }

            return response()->json(['inventory' => $inventory]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los datos del inventario'], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $inventory = Inventory::find($id);

            if (!$inventory) {
                return response()->json(['message' => 'No se encontró inventario para actualizar'], 404);
            }

            $inventory->quantity = $request->quantity;
            $inventory->minimum_stock = $request->minimum_stock;
            $inventory->location = $request->location;
            $inventory->save();

            DB::commit();
            return response()->json(['message' => 'Inventario actualizado correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar el inventario: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $inventory = Inventory::find($id);
            if ($inventory) {
                $inventory->delete();
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Inventario eliminado correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function export()
    {
        $inventory = Inventory::join('providers', 'inventory.provider_id', '=', 'providers.id')
            ->select('providers.razonsocial as provider', 'inventory.*')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventario.csv"',
        ];

        $callback = function() use ($inventory) {
            $file = fopen('php://output', 'w');

            // Headers del CSV
            fputcsv($file, [
                'SKU',
                'Nombre',
                'Descripción',
                'Cantidad',
                'Precio',
                'Categoría',
                'Ubicación',
                'Stock Mínimo',
                'Proveedor',
                'Estado'
            ]);

            // Datos
            foreach ($inventory as $item) {
                fputcsv($file, [
                    $item->sku,
                    $item->name,
                    $item->description,
                    $item->quantity,
                    $item->price,
                    $item->category,
                    $item->location,
                    $item->minimum_stock,
                    $item->provider,
                    $item->active ? 'Activo' : 'Inactivo'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function list()
    {
        try {
            $inventory = Inventory::with(['product', 'provider'])
                ->select('inventory.*')
                ->get();

            return DataTables::of($inventory)
                ->addColumn('product_name', function($item) {
                    return $item->product ? $item->product->name : 'N/A';
                })
                ->addColumn('provider_name', function($item) {
                    return $item->provider ? $item->provider->razonsocial : 'N/A';
                })
                ->addColumn('status', function($item) {
                    return $item->active ? 'Activo' : 'Inactivo';
                })
                ->addColumn('actions', function($item) {
                    return '<button class="btn btn-sm btn-primary edit-btn" data-id="' . $item->id . '">Editar</button> ' .
                           '<button class="btn btn-sm btn-danger delete-btn" data-id="' . $item->id . '">Eliminar</button>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar los datos'], 500);
        }
    }

    public function toggleState($id)
    {
        try {
            $inventory = Inventory::findOrFail($id);
            $inventory->active = !$inventory->active;
            $inventory->save();

            return response()->json([
                'success' => true,
                'message' => 'Estado cambiado correctamente',
                'active' => $inventory->active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado'
            ], 500);
        }
    }
}
