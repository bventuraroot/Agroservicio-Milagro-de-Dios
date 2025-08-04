<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Dte extends Model
{
    use HasFactory;

    protected $table = "dte";

    protected $fillable = [
        'versionJson',
        'ambiente_id',
        'tipoDte',
        'tipoModelo',
        'tipoTransmision',
        'tipoContingencia',
        'idContingencia',
        'nameTable',
        'company_id',
        'company_name',
        'id_doc',
        'codTransaction',
        'desTransaction',
        'type_document',
        'id_doc_Ref1',
        'id_doc_Ref2',
        'type_invalidacion',
        'codEstado',
        'Estado',
        'codigoGeneracion',
        'selloRecibido',
        'fhRecibido',
        'estadoHacienda',
        'nSends',
        'codeMessage',
        'claMessage',
        'descriptionMessage',
        'detailsMessage',
        'created_by',
        'sale_id',
        'json'
    ];

    protected $casts = [
        'fhRecibido' => 'datetime',
        'nSends' => 'integer',
        'versionJson' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Estados de documentos electrónicos
    const ESTADO_EN_COLA = '01';
    const ESTADO_ENVIADO = '02';
    const ESTADO_RECHAZADO = '03';
    const ESTADO_REVISION = '10';

    // Tipos de transacción
    const TRANSACCION_EMISION = '01';
    const TRANSACCION_INVALIDACION = '02';
    const TRANSACCION_CONTINGENCIA = '03';

    // Relaciones
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function ambiente(): BelongsTo
    {
        return $this->belongsTo(Ambiente::class);
    }

    // Scopes
    public function scopeEnCola($query)
    {
        return $query->where('codEstado', self::ESTADO_EN_COLA);
    }

    public function scopeEnviados($query)
    {
        return $query->where('codEstado', self::ESTADO_ENVIADO);
    }

    public function scopeRechazados($query)
    {
        return $query->where('codEstado', self::ESTADO_RECHAZADO);
    }

    public function scopeEnRevision($query)
    {
        return $query->where('codEstado', self::ESTADO_REVISION);
    }

    public function scopeFallidos($query)
    {
        return $query->whereIn('codEstado', [self::ESTADO_RECHAZADO, self::ESTADO_REVISION]);
    }

    public function scopeConErrores($query)
    {
        return $query->whereNotNull('descriptionMessage')
                    ->where('codEstado', '!=', self::ESTADO_ENVIADO);
    }

    public function scopeParaReintento($query)
    {
        return $query->where('codEstado', self::ESTADO_RECHAZADO)
                    ->where('nSends', '<', 3)
                    ->where('updated_at', '<=', now()->subMinutes(30));
    }

    public function scopeNecesitanContingencia($query)
    {
        return $query->where('codEstado', self::ESTADO_RECHAZADO)
                    ->where('nSends', '>=', 3)
                    ->whereNull('idContingencia');
    }

    // Métodos de estado
    public function isEnCola(): bool
    {
        return $this->codEstado === self::ESTADO_EN_COLA;
    }

    public function isEnviado(): bool
    {
        return $this->codEstado === self::ESTADO_ENVIADO;
    }

    public function isRechazado(): bool
    {
        return $this->codEstado === self::ESTADO_RECHAZADO;
    }

    public function isEnRevision(): bool
    {
        return $this->codEstado === self::ESTADO_REVISION;
    }

    public function hasFallado(): bool
    {
        return in_array($this->codEstado, [self::ESTADO_RECHAZADO, self::ESTADO_REVISION]);
    }

    public function puedeReintentar(): bool
    {
        return $this->isRechazado()
            && $this->nSends < 3
            && $this->updated_at <= now()->subMinutes(30);
    }

    public function necesitaContingencia(): bool
    {
        return $this->isRechazado()
            && $this->nSends >= 3
            && !$this->idContingencia;
    }

    // Métodos de utilidad
    public function marcarComoEnviado(array $respuestaHacienda): void
    {
        $this->update([
            'codEstado' => self::ESTADO_ENVIADO,
            'Estado' => 'Enviado',
            'codigoGeneracion' => $respuestaHacienda['codigoGeneracion'] ?? null,
            'selloRecibido' => $respuestaHacienda['selloRecibido'] ?? null,
            'fhRecibido' => isset($respuestaHacienda['fhRecibido']) ?
                Carbon::parse($respuestaHacienda['fhRecibido']) : null,
            'estadoHacienda' => $respuestaHacienda['estadoHacienda'] ?? null,
            'codeMessage' => $respuestaHacienda['codigoMsg'] ?? null,
            'claMessage' => $respuestaHacienda['clasificaMsg'] ?? null,
            'descriptionMessage' => $respuestaHacienda['descripcionMsg'] ?? null,
            'detailsMessage' => $respuestaHacienda['observacionesMsg'] ?? null,
        ]);
    }

    public function marcarComoRechazado(array $respuestaHacienda): void
    {
        $this->update([
            'codEstado' => self::ESTADO_RECHAZADO,
            'Estado' => 'Rechazado',
            'nSends' => $this->nSends + 1,
            'codeMessage' => $respuestaHacienda['codigoMsg'] ?? null,
            'claMessage' => $respuestaHacienda['clasificaMsg'] ?? null,
            'descriptionMessage' => $respuestaHacienda['descripcionMsg'] ?? null,
            'detailsMessage' => $respuestaHacienda['observacionesMsg'] ?? null,
        ]);
    }

    public function marcarEnRevision(string $motivo): void
    {
        $this->update([
            'codEstado' => self::ESTADO_REVISION,
            'Estado' => 'Revisión',
            'descriptionMessage' => $motivo,
        ]);
    }

    public function incrementarIntentos(): void
    {
        $this->increment('nSends');
    }

    public function getJsonDecodedAttribute()
    {
        return $this->json ? json_decode($this->json, true) : null;
    }

    public function getEstadoColorAttribute(): string
    {
        return match($this->codEstado) {
            self::ESTADO_EN_COLA => 'warning',
            self::ESTADO_ENVIADO => 'success',
            self::ESTADO_RECHAZADO => 'danger',
            self::ESTADO_REVISION => 'info',
            default => 'secondary'
        };
    }

    public function getEstadoTextoAttribute(): string
    {
        return match($this->codEstado) {
            self::ESTADO_EN_COLA => 'En Cola',
            self::ESTADO_ENVIADO => 'Enviado',
            self::ESTADO_RECHAZADO => 'Rechazado',
            self::ESTADO_REVISION => 'En Revisión',
            default => 'Desconocido'
        };
    }

    public function getDiasDesdeCreacionAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    public function getEsUrgente(): bool
    {
        return $this->hasFallado() && $this->dias_desde_creacion > 2;
    }
}
