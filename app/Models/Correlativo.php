<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Exception;

class Correlativo extends Model
{
    use HasFactory;

    protected $table = "docs";

    protected $fillable = [
        'id_tipo_doc',
        'serie',
        'inicial',
        'final',
        'actual',
        'estado',
        'id_empresa',
        'hechopor',
        'fechacreacion',
        'resolucion',
        'clase_documento',
        'tipo_documento',
        'tipogeneracion',
        'ambiente',
        'claseDocumento'
    ];

    protected $casts = [
        'inicial' => 'integer',
        'final' => 'integer',
        'actual' => 'integer',
        'estado' => 'integer',
        'id_empresa' => 'integer',
        'hechopor' => 'integer',
        'tipogeneracion' => 'integer',
        'claseDocumento' => 'integer',
        'fechacreacion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Estados de correlativos
    const ESTADO_ACTIVO = 1;
    const ESTADO_INACTIVO = 0;
    const ESTADO_AGOTADO = 2;
    const ESTADO_SUSPENDIDO = 3;

    // Tipos de generación
    const TIPO_GENERACION_NORMAL = 1;
    const TIPO_GENERACION_CONTINGENCIA = 2;

    /**
     * Relación con el tipo de documento
     */
    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(Typedocument::class, 'id_tipo_doc', 'type');
    }

    /**
     * Relación con la empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'id_empresa');
    }

    /**
     * Relación con el usuario que lo creó
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hechopor');
    }

    /**
     * Obtener el siguiente número correlativo
     */
    public function obtenerSiguienteNumero(): int
    {
        return $this->actual;
    }

    /**
     * Incrementar el correlativo actual
     */
    public function incrementarCorrelativo(): bool
    {
        if ($this->actual >= $this->final) {
            $this->estado = self::ESTADO_AGOTADO;
            $this->save();
            throw new Exception("Correlativo agotado. Número actual: {$this->actual}, Final: {$this->final}");
        }

        $this->actual = $this->actual + 1;
        return $this->save();
    }

    /**
     * Verificar si el correlativo está disponible
     */
    public function estaDisponible(): bool
    {
        return $this->estado == self::ESTADO_ACTIVO && $this->actual <= $this->final;
    }

    /**
     * Verificar si el correlativo está agotado
     */
    public function estaAgotado(): bool
    {
        return $this->actual >= $this->final;
    }

    /**
     * Obtener números restantes
     */
    public function numerosRestantes(): int
    {
        return max(0, $this->final - $this->actual + 1);
    }

    /**
     * Obtener porcentaje de uso
     */
    public function porcentajeUso(): float
    {
        $total = $this->final - $this->inicial + 1;
        $usados = $this->actual - $this->inicial;
        return $total > 0 ? round(($usados / $total) * 100, 2) : 0;
    }

    /**
     * Scope para obtener correlativos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    /**
     * Scope para obtener correlativos por empresa
     */
    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('id_empresa', $empresaId);
    }

    /**
     * Scope para obtener correlativos por tipo de documento
     */
    public function scopePorTipoDocumento($query, $tipoDoc)
    {
        return $query->where('id_tipo_doc', $tipoDoc);
    }

    /**
     * Obtener el estado como texto
     */
    public function getEstadoTextoAttribute(): string
    {
        switch ($this->estado) {
            case self::ESTADO_ACTIVO:
                return 'Activo';
            case self::ESTADO_INACTIVO:
                return 'Inactivo';
            case self::ESTADO_AGOTADO:
                return 'Agotado';
            case self::ESTADO_SUSPENDIDO:
                return 'Suspendido';
            default:
                return 'Desconocido';
        }
    }

    /**
     * Obtener el estado con clase CSS para badges
     */
    public function getEstadoBadgeAttribute(): string
    {
        switch ($this->estado) {
            case self::ESTADO_ACTIVO:
                return '<span class="badge bg-success">Activo</span>';
            case self::ESTADO_INACTIVO:
                return '<span class="badge bg-secondary">Inactivo</span>';
            case self::ESTADO_AGOTADO:
                return '<span class="badge bg-danger">Agotado</span>';
            case self::ESTADO_SUSPENDIDO:
                return '<span class="badge bg-warning">Suspendido</span>';
            default:
                return '<span class="badge bg-dark">Desconocido</span>';
        }
    }

    /**
     * Obtener el número de control DTE
     */
    public function generarNumeroControl(): string
    {
        $tipoDoc = $this->tipoDocumento->codemh ?? '01';
        $establecimiento = str_pad($this->tipo_documento ?? '01', 4, '0', STR_PAD_LEFT);
        $numero = str_pad($this->actual, 15, '0', STR_PAD_LEFT);

        return "DTE-{$tipoDoc}-{$establecimiento}-{$numero}";
    }
}
