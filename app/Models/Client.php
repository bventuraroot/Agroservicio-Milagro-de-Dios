<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'firstname',
        'secondname',
        'firstlastname',
        'secondlastname',
        'comercial_name',
        'tel1',
        'tel2',
        'email',
        'address',
        'giro',
        'nit',
        'tpersona',
        'legal',
        'birthday',
        'empresa',
        'companyselected',
        'contribuyente',
        'tipoContribuyente',
        'country',
        'departament',
        'municipio',
        'acteconomica'
    ];

    /**
     * Accessor para obtener el nombre completo del cliente
     * Compatible con el sistema existente
     */
    public function getRazonsocialAttribute()
    {
        if ($this->tpersona == 'J') {
            // Persona jurídica: usar name_contribuyente
            return $this->name_contribuyente ?: $this->comercial_name;
        } else {
            // Persona natural: concatenar nombres
            $nombre = trim($this->firstname . ' ' . $this->secondname);
            $apellidos = trim($this->firstlastname . ' ' . $this->secondlastname);
            return trim($nombre . ' ' . $apellidos);
        }
    }

    /**
     * Relación con la dirección
     */
    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * Relación con el teléfono
     */
    public function phone()
    {
        return $this->belongsTo(Phone::class);
    }

    /**
     * Relación con la actividad económica
     */
    public function economicActivity()
    {
        return $this->belongsTo(Economicactivity::class, 'economicactivity_id');
    }
}
