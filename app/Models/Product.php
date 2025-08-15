<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'state',
        'cfiscal',
        'type',
        'price',
        'description',
        'has_expiration',
        'expiration_days',
        'expiration_type',
        'expiration_notes',
        'image',
        'category',
        'provider_id',
        'marca_id',
        'user_id'
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    /**
     * Relación con los detalles de compras
     */
    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    /**
     * Calcular fecha de caducidad basada en la fecha de compra
     */
    public function calculateExpirationDate($purchaseDate = null)
    {
        if (!$this->has_expiration || !$this->expiration_days) {
            return null;
        }

        $date = $purchaseDate ? Carbon::parse($purchaseDate) : Carbon::now();

        return match($this->expiration_type) {
            'days' => $date->addDays($this->expiration_days),
            'months' => $date->addMonths($this->expiration_days),
            'years' => $date->addYears($this->expiration_days),
            default => $date->addDays($this->expiration_days)
        };
    }

    /**
     * Verificar si el producto tiene caducidad configurada
     */
    public function hasExpirationConfigured()
    {
        return $this->has_expiration && $this->expiration_days > 0;
    }
}
