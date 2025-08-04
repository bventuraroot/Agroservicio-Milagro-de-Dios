<?php

namespace App\Services;

use App\Models\Correlativo;
use App\Models\Typedocument;
use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class CorrelativoService
{
    /**
     * Obtener el siguiente correlativo disponible para un tipo de documento y empresa
     */
    public function obtenerSiguienteCorrelativo(string $tipoDocumento, int $empresaId): ?Correlativo
    {
        return Correlativo::join('typedocuments as tdoc', 'tdoc.type', '=', 'docs.id_tipo_doc')
            ->where('tdoc.id', '=', $tipoDocumento)
            ->where('docs.id_empresa', '=', $empresaId)
            ->where('docs.estado', '=', Correlativo::ESTADO_ACTIVO)
            ->where('docs.actual', '<=', 'docs.final')
            ->select('docs.*')
            ->first();
    }

    /**
     * Obtener correlativo específico por ID de tipo de documento y empresa
     */
    public function obtenerCorrelativoPorTipo(int $tipoDocumentoId, int $empresaId): ?Correlativo
    {
        return Correlativo::join('typedocuments as tdoc', 'tdoc.type', '=', 'docs.id_tipo_doc')
            ->where('tdoc.id', '=', $tipoDocumentoId)
            ->where('docs.id_empresa', '=', $empresaId)
            ->select('docs.*')
            ->first();
    }

    /**
     * Reservar y obtener el siguiente número correlativo
     */
    public function reservarSiguienteNumero(int $tipoDocumentoId, int $empresaId): array
    {
        DB::beginTransaction();

        try {
            $correlativo = $this->obtenerCorrelativoPorTipo($tipoDocumentoId, $empresaId);

            if (!$correlativo) {
                throw new Exception("No se encontró correlativo para el tipo de documento y empresa especificados");
            }

            if (!$correlativo->estaDisponible()) {
                throw new Exception("El correlativo no está disponible o está agotado");
            }

            $numeroActual = $correlativo->actual;
            $correlativo->incrementarCorrelativo();

            DB::commit();

            return [
                'numero' => $numeroActual,
                'correlativo_id' => $correlativo->id,
                'numero_control' => $correlativo->generarNumeroControl(),
                'restantes' => $correlativo->numerosRestantes()
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Crear un nuevo correlativo
     */
    public function crearCorrelativo(array $datos): Correlativo
    {
        // Validar que no exista ya un correlativo activo para el mismo tipo y empresa
        $existente = Correlativo::where('id_tipo_doc', $datos['id_tipo_doc'])
            ->where('id_empresa', $datos['id_empresa'])
            ->where('estado', Correlativo::ESTADO_ACTIVO)
            ->first();

        if ($existente) {
            throw new Exception("Ya existe un correlativo activo para este tipo de documento y empresa");
        }

        // Validar rangos
        if ($datos['inicial'] > $datos['final']) {
            throw new Exception("El número inicial no puede ser mayor que el final");
        }

        if ($datos['actual'] < $datos['inicial'] || $datos['actual'] > $datos['final']) {
            throw new Exception("El número actual debe estar entre el inicial y final");
        }

        $datos['hechopor'] = Auth::id();
        $datos['fechacreacion'] = now();
        $datos['estado'] = $datos['estado'] ?? Correlativo::ESTADO_ACTIVO;

        return Correlativo::create($datos);
    }

    /**
     * Actualizar correlativo existente
     */
    public function actualizarCorrelativo(int $id, array $datos): Correlativo
    {
        $correlativo = Correlativo::findOrFail($id);

        // Validar rangos si se están actualizando
        if (isset($datos['inicial']) && isset($datos['final']) && $datos['inicial'] > $datos['final']) {
            throw new Exception("El número inicial no puede ser mayor que el final");
        }

        if (isset($datos['actual'])) {
            $inicial = $datos['inicial'] ?? $correlativo->inicial;
            $final = $datos['final'] ?? $correlativo->final;

            if ($datos['actual'] < $inicial || $datos['actual'] > $final) {
                throw new Exception("El número actual debe estar entre el inicial y final");
            }
        }

        $correlativo->update($datos);
        return $correlativo->fresh();
    }

    /**
     * Obtener estadísticas de correlativos por empresa
     */
    public function obtenerEstadisticas(int $empresaId): array
    {
        $correlativos = Correlativo::with('tipoDocumento')
            ->where('id_empresa', $empresaId)
            ->get();

        $estadisticas = [
            'total' => $correlativos->count(),
            'activos' => $correlativos->where('estado', Correlativo::ESTADO_ACTIVO)->count(),
            'agotados' => $correlativos->where('estado', Correlativo::ESTADO_AGOTADO)->count(),
            'por_tipo' => [],
            'alertas' => []
        ];

        foreach ($correlativos as $correlativo) {
            $tipo = $correlativo->tipoDocumento->description ?? 'Sin definir';

            if (!isset($estadisticas['por_tipo'][$tipo])) {
                $estadisticas['por_tipo'][$tipo] = [
                    'total' => 0,
                    'restantes' => 0,
                    'porcentaje_uso' => 0
                ];
            }

            $estadisticas['por_tipo'][$tipo]['total']++;
            $estadisticas['por_tipo'][$tipo]['restantes'] += $correlativo->numerosRestantes();
            $estadisticas['por_tipo'][$tipo]['porcentaje_uso'] = $correlativo->porcentajeUso();

            // Generar alertas para correlativos con menos del 10% restante
            if ($correlativo->porcentajeUso() > 90 && $correlativo->estado == Correlativo::ESTADO_ACTIVO) {
                $estadisticas['alertas'][] = [
                    'tipo' => 'warning',
                    'mensaje' => "Correlativo {$tipo} - Serie {$correlativo->serie} está por agotarse",
                    'restantes' => $correlativo->numerosRestantes()
                ];
            }
        }

        return $estadisticas;
    }

    /**
     * Obtener todos los correlativos con filtros
     */
    public function obtenerCorrelativos(array $filtros = []): Collection
    {
        $query = Correlativo::with(['tipoDocumento', 'empresa', 'usuario']);

        if (isset($filtros['empresa_id'])) {
            $query->where('id_empresa', $filtros['empresa_id']);
        }

        if (isset($filtros['tipo_documento'])) {
            $query->where('id_tipo_doc', $filtros['tipo_documento']);
        }

        if (isset($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (isset($filtros['solo_activos']) && $filtros['solo_activos']) {
            $query->activos();
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Validar disponibilidad de correlativo antes de usarlo
     */
    public function validarDisponibilidad(int $tipoDocumentoId, int $empresaId): array
    {
        $correlativo = $this->obtenerCorrelativoPorTipo($tipoDocumentoId, $empresaId);

        if (!$correlativo) {
            return [
                'disponible' => false,
                'mensaje' => 'No se encontró correlativo configurado',
                'datos' => null
            ];
        }

        if (!$correlativo->estaDisponible()) {
            return [
                'disponible' => false,
                'mensaje' => $correlativo->estaAgotado() ? 'Correlativo agotado' : 'Correlativo inactivo',
                'datos' => [
                    'actual' => $correlativo->actual,
                    'final' => $correlativo->final,
                    'estado' => $correlativo->estado_texto
                ]
            ];
        }

        return [
            'disponible' => true,
            'mensaje' => 'Correlativo disponible',
            'datos' => [
                'actual' => $correlativo->actual,
                'restantes' => $correlativo->numerosRestantes(),
                'porcentaje_uso' => $correlativo->porcentajeUso()
            ]
        ];
    }

    /**
     * Reactivar correlativo agotado con nuevos rangos
     */
    public function reactivarCorrelativo(int $id, int $nuevoInicial, int $nuevoFinal): Correlativo
    {
        DB::beginTransaction();

        try {
            $correlativo = Correlativo::findOrFail($id);

            if ($nuevoInicial > $nuevoFinal) {
                throw new Exception("El número inicial no puede ser mayor que el final");
            }

            if ($nuevoInicial <= $correlativo->final) {
                throw new Exception("El nuevo rango debe empezar después del rango anterior");
            }

            $correlativo->update([
                'inicial' => $nuevoInicial,
                'final' => $nuevoFinal,
                'actual' => $nuevoInicial,
                'estado' => Correlativo::ESTADO_ACTIVO
            ]);

            DB::commit();
            return $correlativo->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener lista de tipos de documento disponibles
     */
    public function obtenerTiposDocumento(): Collection
    {
        return Typedocument::select('id', 'type', 'description', 'codemh')
            ->orderBy('description')
            ->get();
    }

    /**
     * Obtener lista de empresas disponibles
     */
    public function obtenerEmpresas(): Collection
    {
        return Company::select('id', 'name', 'nit')
            ->orderBy('name')
            ->get();
    }
}
