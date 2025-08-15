<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PurchaseInventoryService
{
    /**
     * Agregar productos de una compra al inventario
     */
    public function addPurchaseToInventory(Purchase $purchase)
    {
        DB::beginTransaction();

        try {
            $details = $purchase->details()->where('added_to_inventory', false)->get();

            foreach ($details as $detail) {
                $this->addDetailToInventory($detail);
            }

            DB::commit();

            Log::info('Productos agregados al inventario desde compra', [
                'purchase_id' => $purchase->id,
                'details_count' => $details->count()
            ]);

            return [
                'success' => true,
                'message' => 'Productos agregados al inventario correctamente',
                'details_processed' => $details->count()
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error agregando productos al inventario', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error al agregar productos al inventario: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Agregar un detalle específico al inventario
     */
    public function addDetailToInventory(PurchaseDetail $detail)
    {
        $product = $detail->product;

        // Buscar inventario existente para este producto
        $inventory = Inventory::where('product_id', $product->id)->first();

        if (!$inventory) {
            // Crear nuevo registro de inventario
            $inventory = Inventory::create([
                'product_id' => $product->id,
                'quantity' => $detail->quantity,
                'minimum_stock' => 0,
                'location' => 'Almacén Principal',
                'expiration_date' => $detail->expiration_date,
                'batch_number' => $detail->batch_number,
                'expiring_quantity' => $detail->expiration_date ? $detail->quantity : 0,
                'expiration_warning_sent' => false,
                'last_expiration_check' => now()
            ]);
        } else {
            // Actualizar inventario existente
            $inventory->quantity += $detail->quantity;

            // Si el producto tiene fecha de caducidad, actualizar la cantidad que vence
            if ($detail->expiration_date) {
                $inventory->expiring_quantity += $detail->quantity;

                // Si no hay fecha de caducidad en el inventario o la nueva es más temprana, actualizarla
                if (!$inventory->expiration_date || $detail->expiration_date < $inventory->expiration_date) {
                    $inventory->expiration_date = $detail->expiration_date;
                }
            }

            $inventory->save();
        }

        // Marcar el detalle como agregado al inventario
        $detail->update(['added_to_inventory' => true]);

        Log::info('Detalle agregado al inventario', [
            'detail_id' => $detail->id,
            'product_id' => $product->id,
            'quantity' => $detail->quantity,
            'inventory_quantity' => $inventory->quantity
        ]);
    }

    /**
     * Remover productos del inventario (para cancelaciones o devoluciones)
     */
    public function removePurchaseFromInventory(Purchase $purchase)
    {
        DB::beginTransaction();

        try {
            $details = $purchase->details()->where('added_to_inventory', true)->get();

            foreach ($details as $detail) {
                $this->removeDetailFromInventory($detail);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Productos removidos del inventario correctamente',
                'details_processed' => $details->count()
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error removiendo productos del inventario', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error al remover productos del inventario: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Remover un detalle específico del inventario
     */
    public function removeDetailFromInventory(PurchaseDetail $detail)
    {
        $inventory = Inventory::where('product_id', $detail->product_id)->first();

        if ($inventory) {
            $inventory->quantity = max(0, $inventory->quantity - $detail->quantity);

            // Si el producto tiene fecha de caducidad, actualizar la cantidad que vence
            if ($detail->expiration_date) {
                $inventory->expiring_quantity = max(0, $inventory->expiring_quantity - $detail->quantity);
            }

            // Si no quedan productos, eliminar el registro de inventario
            if ($inventory->quantity <= 0) {
                $inventory->delete();
            } else {
                $inventory->save();
            }
        }

        // Marcar el detalle como removido del inventario
        $detail->update(['added_to_inventory' => false]);
    }

    /**
     * Verificar productos próximos a vencer
     */
    public function checkExpiringProducts($days = 30)
    {
        $expiringProducts = Inventory::whereNotNull('expiration_date')
            ->where('expiration_date', '<=', now()->addDays($days))
            ->where('expiration_date', '>', now())
            ->where('quantity', '>', 0)
            ->with('product')
            ->get();

        $results = [
            'critical' => collect(), // 7 días o menos
            'warning' => collect(),  // 8-30 días
            'total' => $expiringProducts->count()
        ];

        foreach ($expiringProducts as $inventory) {
            $daysUntilExpiration = $inventory->getDaysUntilExpiration();

            if ($daysUntilExpiration <= 7) {
                $results['critical']->push($inventory);
            } else {
                $results['warning']->push($inventory);
            }
        }

        return $results;
    }

    /**
     * Obtener productos vencidos
     */
    public function getExpiredProducts()
    {
        return Inventory::whereNotNull('expiration_date')
            ->where('expiration_date', '<', now())
            ->where('quantity', '>', 0)
            ->with('product')
            ->get();
    }

    /**
     * Actualizar fechas de caducidad basadas en la configuración del producto
     */
    public function updateExpirationDates(PurchaseDetail $detail)
    {
        $product = $detail->product;

        if ($product->hasExpirationConfigured() && !$detail->expiration_date) {
            $expirationDate = $product->calculateExpirationDate($detail->purchase->date);
            $detail->update(['expiration_date' => $expirationDate]);
        }
    }

    /**
     * Generar número de lote automático
     */
    public function generateBatchNumber(PurchaseDetail $detail)
    {
        if (!$detail->batch_number) {
            $purchase = $detail->purchase;
            $product = $detail->product;

            $batchNumber = sprintf(
                'LOT-%s-%s-%s',
                $purchase->date->format('Ymd'),
                $product->code ?? $product->id,
                str_pad($detail->id, 4, '0', STR_PAD_LEFT)
            );

            $detail->update(['batch_number' => $batchNumber]);
        }
    }

    /**
     * Obtener reporte de inventario con caducidad
     */
    public function getInventoryExpirationReport()
    {
        return Inventory::with(['product', 'product.provider'])
            ->whereNotNull('expiration_date')
            ->where('quantity', '>', 0)
            ->orderBy('expiration_date')
            ->get()
            ->groupBy(function ($inventory) {
                $status = $inventory->getExpirationStatus();
                return match($status) {
                    'expired' => 'Vencidos',
                    'critical' => 'Críticos (≤7 días)',
                    'warning' => 'Advertencia (8-30 días)',
                    'ok' => 'OK (>30 días)',
                    default => 'Sin clasificar'
                };
            });
    }
}
