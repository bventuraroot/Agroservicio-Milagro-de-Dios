<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salesdetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'amountp',
        'pricesale',
        'priceunit',
        'nosujeta',
        'exempt',
        'detained',
        'detained13',
        'renta',
        'fee',
        'feeiva',
        'reserva',
        'ruta',
        'destino',
        'linea',
        'canal',
        'user_id'
    ];

    protected $casts = [
        'amountp' => 'integer',
        'pricesale' => 'decimal:2',
        'priceunit' => 'decimal:2',
        'nosujeta' => 'decimal:2',
        'exempt' => 'decimal:2',
        'detained' => 'decimal:2',
        'detained13' => 'decimal:2',
        'renta' => 'decimal:2',
        'fee' => 'decimal:2',
        'feeiva' => 'decimal:2'
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
