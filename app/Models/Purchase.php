<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'provider_id',
        'company_id',
        'number',
        'date',
        'exenta',
        'gravada',
        'iva',
        'contrns',
        'fovial',
        'iretenido',
        'otros',
        'total',
        'fingreso',
        'periodo',
        'user_id'
    ];

    protected $casts = [
        'date' => 'date',
        'fingreso' => 'date',
        'exenta' => 'decimal:2',
        'gravada' => 'decimal:2',
        'iva' => 'decimal:2',
        'contrns' => 'decimal:2',
        'fovial' => 'decimal:2',
        'iretenido' => 'decimal:2',
        'otros' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    /**
     * Relación con el proveedor
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Relación con la empresa
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relación con el usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con los detalles de la compra
     */
    public function details()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    /**
     * Calcular totales de la compra
     */
    public function calculateTotals()
    {
        $details = $this->details;

        $exenta = $details->sum('subtotal');
        $gravada = $details->sum('subtotal');
        $iva = $details->sum('tax_amount');
        $total = $details->sum('total_amount');

        $this->update([
            'exenta' => $exenta,
            'gravada' => $gravada,
            'iva' => $iva,
            'total' => $total
        ]);
    }

    /**
     * Verificar si todos los detalles han sido agregados al inventario
     */
    public function allDetailsAddedToInventory()
    {
        return $this->details()->where('added_to_inventory', false)->count() === 0;
    }

    /**
     * Obtener productos próximos a vencer en esta compra
     */
    public function getExpiringProducts($days = 30)
    {
        return $this->details()
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<=', now()->addDays($days))
            ->where('expiration_date', '>', now())
            ->with('product')
            ->get();
    }

    /**
     * Obtener productos vencidos en esta compra
     */
    public function getExpiredProducts()
    {
        return $this->details()
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<', now())
            ->with('product')
            ->get();
    }
}
