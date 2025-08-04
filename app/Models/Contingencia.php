<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Contingencia extends Model
{
    use HasFactory;

    protected $table = "contingencias";

    protected $fillable = [
        'idEmpresa',
        'idTienda',
        'codInterno',
        'versionJson',
        'ambiente',
        'codEstado',
        'estado',
        'codigoGeneracion',
        'fechaCreacion',
        'horaCreacion',
        'fInicio',
        'fFin',
        'hInicio',
        'hFin',
        'tipoContingencia',
        'motivoContingencia',
        'nombreResponsable',
        'tipoDocResponsable',
        'nuDocResponsable',
        'selloRecibido',
        'fhRecibido',
        'codEstadoHacienda',
        'estadoHacienda',
        'codigoMsg',
        'clasificaMsg',
        'descripcionMsg',
        'observacionesMsg',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'fechaCreacion' => 'date',
        'fInicio' => 'date',
        'fFin' => 'date',
        'fhRecibido' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'tipoContingencia' => 'integer'
    ];

    // Estados de contingencias
    const ESTADO_EN_COLA = '01';
    const ESTADO_ENVIADO = '02';
    const ESTADO_RECHAZADO = '03';
    const ESTADO_REVISION = '10';

    // Tipos de contingencia
    const TIPO_NO_DISPONIBILIDAD_MH = 1;
    const TIPO_NO_DISPONIBILIDAD_EMISOR = 2;
    const TIPO_FALLA_INTERNET = 3;
    const TIPO_FALLA_ENERGIA = 4;
    const TIPO_OTRO = 5;

    // Relaciones
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'idEmpresa');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Sale::class, 'id_contingencia');
    }

    public function dtes(): HasMany
    {
        return $this->hasMany(Dte::class, 'idContingencia');
    }

    // Scopes
    public function scopeEnCola($query)
    {
        return $query->where('codEstado', self::ESTADO_EN_COLA);
    }

    public function scopeEnviadas($query)
    {
        return $query->where('codEstado', self::ESTADO_ENVIADO);
    }

    public function scopeRechazadas($query)
    {
        return $query->where('codEstado', self::ESTADO_RECHAZADO);
    }

    public function scopeEnRevision($query)
    {
        return $query->where('codEstado', self::ESTADO_REVISION);
    }

    public function scopeActivas($query)
    {
        return $query->whereIn('codEstado', [self::ESTADO_EN_COLA, self::ESTADO_ENVIADO]);
    }

    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('idEmpresa', $empresaId);
    }

    public function scopeEnRango($query, Carbon $fechaInicio, Carbon $fechaFin)
    {
        return $query->where('fInicio', '>=', $fechaInicio)
                    ->where('fFin', '<=', $fechaFin);
    }

    public function scopeVencidas($query)
    {
        return $query->where('fFin', '<', now()->format('Y-m-d'))
                    ->where('codEstado', '!=', self::ESTADO_ENVIADO);
    }

    public function scopeProximasAVencer($query, int $dias = 1)
    {
        return $query->where('fFin', '<=', now()->addDays($dias)->format('Y-m-d'))
                    ->where('fFin', '>=', now()->format('Y-m-d'))
                    ->where('codEstado', '!=', self::ESTADO_ENVIADO);
    }

    // Métodos de estado
    public function isEnCola(): bool
    {
        return $this->codEstado === self::ESTADO_EN_COLA;
    }

    public function isEnviada(): bool
    {
        return $this->codEstado === self::ESTADO_ENVIADO;
    }

    public function isRechazada(): bool
    {
        return $this->codEstado === self::ESTADO_RECHAZADO;
    }

    public function isEnRevision(): bool
    {
        return $this->codEstado === self::ESTADO_REVISION;
    }

    public function isActiva(): bool
    {
        return in_array($this->codEstado, [self::ESTADO_EN_COLA, self::ESTADO_ENVIADO]);
    }

    public function isVencida(): bool
    {
        return $this->fFin < now()->format('Y-m-d') && !$this->isEnviada();
    }

    public function proximaAVencer(int $dias = 1): bool
    {
        $fechaLimite = now()->addDays($dias)->format('Y-m-d');
        return $this->fFin <= $fechaLimite &&
               $this->fFin >= now()->format('Y-m-d') &&
               !$this->isEnviada();
    }

    public function puedeSerAutorizada(): bool
    {
        return $this->isEnCola() && !$this->isVencida();
    }

    // Métodos de utilidad
    public function marcarComoEnviada(array $respuestaHacienda): void
    {
        $this->update([
            'codEstado' => self::ESTADO_ENVIADO,
            'estado' => 'Enviado',
            'selloRecibido' => $respuestaHacienda['selloRecibido'] ?? null,
            'fhRecibido' => isset($respuestaHacienda['fhRecibido']) ?
                Carbon::parse($respuestaHacienda['fhRecibido']) : null,
            'estadoHacienda' => $respuestaHacienda['estadoHacienda'] ?? null,
            'codigoMsg' => $respuestaHacienda['codigoMsg'] ?? null,
            'clasificaMsg' => $respuestaHacienda['clasificaMsg'] ?? null,
            'descripcionMsg' => $respuestaHacienda['descripcionMsg'] ?? null,
            'observacionesMsg' => $respuestaHacienda['observacionesMsg'] ?? null,
        ]);
    }

    public function marcarComoRechazada(array $respuestaHacienda): void
    {
        $this->update([
            'codEstado' => self::ESTADO_RECHAZADO,
            'estado' => 'Rechazado',
            'codigoMsg' => $respuestaHacienda['codigoMsg'] ?? null,
            'clasificaMsg' => $respuestaHacienda['clasificaMsg'] ?? null,
            'descripcionMsg' => $respuestaHacienda['descripcionMsg'] ?? null,
            'observacionesMsg' => $respuestaHacienda['observacionesMsg'] ?? null,
        ]);
    }

    public function marcarEnRevision(string $motivo): void
    {
        $this->update([
            'codEstado' => self::ESTADO_REVISION,
            'estado' => 'Revisión',
            'descripcionMsg' => $motivo,
        ]);
    }

    public function asignarDocumentos(array $documentoIds): void
    {
        Sale::whereIn('id', $documentoIds)
            ->update(['id_contingencia' => $this->id]);
    }

    public function generarCodigoGeneracion(): string
    {
        if (!$this->codigoGeneracion) {
            $this->update([
                'codigoGeneracion' => strtoupper(Str::uuid()->toString())
            ]);
        }

        return $this->codigoGeneracion;
    }

    // Accessors
    public function getTipoContingenciaTextoAttribute(): string
    {
        return match($this->tipoContingencia) {
            self::TIPO_NO_DISPONIBILIDAD_MH => 'No disponibilidad de sistema del MH',
            self::TIPO_NO_DISPONIBILIDAD_EMISOR => 'No disponibilidad de sistema del emisor',
            self::TIPO_FALLA_INTERNET => 'Falla en el suministro de servicio de Internet del Emisor',
            self::TIPO_FALLA_ENERGIA => 'Falla en el suministro de servicio de energía eléctrica del emisor',
            self::TIPO_OTRO => 'Otro motivo',
            default => 'Tipo desconocido'
        };
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

    public function getDiasVigenciaAttribute(): int
    {
        return Carbon::parse($this->fInicio)->diffInDays(Carbon::parse($this->fFin)) + 1;
    }

    public function getDiasRestantesAttribute(): int
    {
        $fechaFin = Carbon::parse($this->fFin);
        $hoy = now();

        if ($fechaFin < $hoy) {
            return 0;
        }

        return $hoy->diffInDays($fechaFin);
    }

    public function getContadorDocumentosAttribute(): int
    {
        return $this->documentos()->count();
    }

    public function getRangoFechasAttribute(): string
    {
        return Carbon::parse($this->fInicio)->format('d/m/Y') . ' - ' .
               Carbon::parse($this->fFin)->format('d/m/Y');
    }

    // Métodos estáticos
    public static function crearAutomatica(int $empresaId, int $tipoContingencia, string $motivo): self
    {
        $fechaInicio = now();
        $fechaFin = now()->addDays(2); // 2 días de vigencia por defecto

        return self::create([
            'idEmpresa' => $empresaId,
            'versionJson' => '3',
            'ambiente' => '01', // Producción por defecto
            'codEstado' => self::ESTADO_EN_COLA,
            'estado' => 'En Cola',
            'tipoContingencia' => $tipoContingencia,
            'motivoContingencia' => $motivo,
            'nombreResponsable' => 'Sistema Automático',
            'tipoDocResponsable' => '13',
            'nuDocResponsable' => '00000000-0',
            'fechaCreacion' => $fechaInicio->format('Y-m-d'),
            'horaCreacion' => $fechaInicio->format('H:i:s'),
            'fInicio' => $fechaInicio->format('Y-m-d'),
            'fFin' => $fechaFin->format('Y-m-d'),
            'hInicio' => $fechaInicio->format('H:i:s'),
            'hFin' => $fechaFin->format('H:i:s'),
            'codigoGeneracion' => strtoupper(Str::uuid()->toString()),
            'created_by' => 'Sistema'
        ]);
    }

    public static function buscarActivaPorEmpresa(int $empresaId): ?self
    {
        return self::where('idEmpresa', $empresaId)
                  ->activas()
                  ->orderBy('created_at', 'desc')
                  ->first();
    }
}
