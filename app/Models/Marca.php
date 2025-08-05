<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image',
        'status',
        'user_id'
    ];

    /**
     * Relación con productos
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Relación con usuario que creó la marca
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
