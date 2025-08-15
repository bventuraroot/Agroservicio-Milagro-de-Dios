<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PurchaseDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
        'tax_amount',
        'total_amount',
        'expiration_date',
        'batch_number',
        'notes',
        'added_to_inventory',
        'user_id'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'expiration_date' => 'date',
        'added_to_inventory' => 'boolean'
    ];

    /**
     * Relación con la compra
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Relación con el producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación con el usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verificar si el producto está próximo a vencer
     */
    public function isExpiringSoon($days = 30)
    {
        if (!$this->expiration_date) {
            return false;
        }

        return Carbon::now()->diffInDays($this->expiration_date, false) <= $days;
    }

    /**
     * Verificar si el producto ya venció
     */
    public function isExpired()
    {
        if (!$this->expiration_date) {
            return false;
        }

        return Carbon::now()->isAfter($this->expiration_date);
    }

    /**
     * Obtener días restantes hasta la caducidad
     */
    public function getDaysUntilExpiration()
    {
        if (!$this->expiration_date) {
            return null;
        }

        return Carbon::now()->diffInDays($this->expiration_date, false);
    }

    /**
     * Obtener el estado de caducidad
     */
    public function getExpirationStatus()
    {
        if (!$this->expiration_date) {
            return 'no_expiration';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->isExpiringSoon(7)) {
            return 'critical';
        }

        if ($this->isExpiringSoon(30)) {
            return 'warning';
        }

        return 'ok';
    }

    /**
     * Obtener el color del estado de caducidad
     */
    public function getExpirationStatusColor()
    {
        return match($this->getExpirationStatus()) {
            'expired' => 'danger',
            'critical' => 'danger',
            'warning' => 'warning',
            'ok' => 'success',
            default => 'secondary'
        };
    }

    /**
     * Obtener el texto del estado de caducidad
     */
    public function getExpirationStatusText()
    {
        return match($this->getExpirationStatus()) {
            'expired' => 'Vencido',
            'critical' => 'Crítico',
            'warning' => 'Advertencia',
            'ok' => 'OK',
            default => 'Sin caducidad'
        };
    }

}
