<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
