<?php

namespace App\Http\Controllers;

use App\Models\Correlativo;
use App\Services\CorrelativoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Exception;

class CorrelativoController extends Controller
{
    protected $correlativoService;

    public function __construct(CorrelativoService $correlativoService)
    {
        $this->correlativoService = $correlativoService;

        // Middleware de permisos
        $this->middleware('permission:correlativos.index')->only(['index', 'show']);
        $this->middleware('permission:correlativos.create')->only(['create', 'store']);
        $this->middleware('permission:correlativos.edit')->only(['edit', 'update']);
        $this->middleware('permission:correlativos.destroy')->only(['destroy']);
        $this->middleware('permission:correlativos.estadisticas')->only(['estadisticas', 'apiEstadisticas']);
        $this->middleware('permission:correlativos.reactivar')->only(['reactivar']);
        $this->middleware('permission:correlativos.cambiar-estado')->only(['cambiarEstado']);
        $this->middleware('permission:correlativos.api.siguiente-numero')->only(['apiSiguienteNumero']);
        $this->middleware('permission:correlativos.api.validar-disponibilidad')->only(['apiValidarDisponibilidad']);
    }

    /**
     * Mostrar lista de correlativos
     */
    public function index(Request $request): View
    {
        $filtros = $request->only(['empresa_id', 'tipo_documento', 'estado']);
        $correlativos = $this->correlativoService->obtenerCorrelativos($filtros);

        $tiposDocumento = $this->correlativoService->obtenerTiposDocumento();
        $empresas = $this->correlativoService->obtenerEmpresas();

        return view('correlativos.index', compact('correlativos', 'tiposDocumento', 'empresas', 'filtros'));
    }

    /**
     * Mostrar formulario para crear nuevo correlativo
     */
    public function create(): View
    {
        $tiposDocumento = $this->correlativoService->obtenerTiposDocumento();
        $empresas = $this->correlativoService->obtenerEmpresas();

        return view('correlativos.create', compact('tiposDocumento', 'empresas'));
    }

    /**
     * Almacenar nuevo correlativo
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'id_tipo_doc' => 'required|string|max:4',
            'serie' => 'required|string|max:50',
            'inicial' => 'required|integer|min:1',
            'final' => 'required|integer|min:1',
            'actual' => 'required|integer|min:1',
            'id_empresa' => 'required|integer|exists:companies,id',
            'resolucion' => 'nullable|string|max:50',
            'clase_documento' => 'nullable|string|max:1',
            'tipo_documento' => 'nullable|string|max:2',
            'tipogeneracion' => 'nullable|integer',
            'ambiente' => 'nullable|string|max:2',
            'claseDocumento' => 'nullable|integer',
            'estado' => 'sometimes|integer|in:0,1,2,3'
        ]);

        try {
            $correlativo = $this->correlativoService->crearCorrelativo($request->all());

            return redirect()->route('correlativos.index')
                ->with('success', 'Correlativo creado exitosamente.');

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear correlativo: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar correlativo específico
     */
    public function show(int $id): View
    {
        $correlativo = Correlativo::with(['tipoDocumento', 'empresa', 'usuario'])->findOrFail($id);

        return view('correlativos.show', compact('correlativo'));
    }

    /**
     * Mostrar formulario para editar correlativo
     */
    public function edit(int $id): View
    {
        $correlativo = Correlativo::findOrFail($id);
        $tiposDocumento = $this->correlativoService->obtenerTiposDocumento();
        $empresas = $this->correlativoService->obtenerEmpresas();

        return view('correlativos.edit', compact('correlativo', 'tiposDocumento', 'empresas'));
    }

    /**
     * Actualizar correlativo
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'serie' => 'required|string|max:50',
            'inicial' => 'required|integer|min:1',
            'final' => 'required|integer|min:1',
            'actual' => 'required|integer|min:1',
            'resolucion' => 'nullable|string|max:50',
            'clase_documento' => 'nullable|string|max:1',
            'tipo_documento' => 'nullable|string|max:2',
            'tipogeneracion' => 'nullable|integer',
            'ambiente' => 'nullable|string|max:2',
            'claseDocumento' => 'nullable|integer',
            'estado' => 'sometimes|integer|in:0,1,2,3'
        ]);

        try {
            $correlativo = $this->correlativoService->actualizarCorrelativo($id, $request->all());

            return redirect()->route('correlativos.index')
                ->with('success', 'Correlativo actualizado exitosamente.');

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar correlativo: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar correlativo
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $correlativo = Correlativo::findOrFail($id);

            // No permitir eliminar correlativos activos con uso
            if ($correlativo->estado == Correlativo::ESTADO_ACTIVO && $correlativo->actual > $correlativo->inicial) {
                return redirect()->back()
                    ->with('error', 'No se puede eliminar un correlativo activo que ya ha sido utilizado.');
            }

            $correlativo->delete();

            return redirect()->route('correlativos.index')
                ->with('success', 'Correlativo eliminado exitosamente.');

        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al eliminar correlativo: ' . $e->getMessage());
        }
    }

    /**
     * API: Obtener siguiente número correlativo
     */
    public function apiSiguienteNumero(Request $request): JsonResponse
    {
        $request->validate([
            'tipo_documento_id' => 'required|integer',
            'empresa_id' => 'required|integer'
        ]);

        try {
            $resultado = $this->correlativoService->reservarSiguienteNumero(
                $request->tipo_documento_id,
                $request->empresa_id
            );

            return response()->json([
                'success' => true,
                'data' => $resultado
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * API: Validar disponibilidad de correlativo
     */
    public function apiValidarDisponibilidad(Request $request): JsonResponse
    {
        $request->validate([
            'tipo_documento_id' => 'required|integer',
            'empresa_id' => 'required|integer'
        ]);

        $resultado = $this->correlativoService->validarDisponibilidad(
            $request->tipo_documento_id,
            $request->empresa_id
        );

        return response()->json($resultado);
    }

    /**
     * Mostrar estadísticas de correlativos
     */
    public function estadisticas(Request $request): View
    {
        $empresaId = $request->get('empresa_id', auth()->user()->company_id ?? 1);
        $estadisticas = $this->correlativoService->obtenerEstadisticas($empresaId);
        $empresas = $this->correlativoService->obtenerEmpresas();

        return view('correlativos.estadisticas', compact('estadisticas', 'empresas', 'empresaId'));
    }

    /**
     * API: Obtener estadísticas en formato JSON
     */
    public function apiEstadisticas(Request $request): JsonResponse
    {
        $empresaId = $request->get('empresa_id', auth()->user()->company_id ?? 1);
        $estadisticas = $this->correlativoService->obtenerEstadisticas($empresaId);

        return response()->json($estadisticas);
    }

    /**
     * Reactivar correlativo agotado
     */
    public function reactivar(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'nuevo_inicial' => 'required|integer|min:1',
            'nuevo_final' => 'required|integer|min:1'
        ]);

        try {
            $correlativo = $this->correlativoService->reactivarCorrelativo(
                $id,
                $request->nuevo_inicial,
                $request->nuevo_final
            );

            return redirect()->route('correlativos.index')
                ->with('success', 'Correlativo reactivado exitosamente.');

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al reactivar correlativo: ' . $e->getMessage());
        }
    }

    /**
     * Cambiar estado de correlativo
     */
    public function cambiarEstado(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'estado' => 'required|integer|in:0,1,2,3'
        ]);

        try {
            $correlativo = $this->correlativoService->actualizarCorrelativo($id, [
                'estado' => $request->estado
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'new_state' => $correlativo->estado_texto
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener correlativos por empresa (AJAX)
     */
    public function porEmpresa(Request $request): JsonResponse
    {
        $empresaId = $request->get('empresa_id');

        if (!$empresaId) {
            return response()->json([]);
        }

        $correlativos = $this->correlativoService->obtenerCorrelativos(['empresa_id' => $empresaId]);

        return response()->json($correlativos->map(function ($correlativo) {
            return [
                'id' => $correlativo->id,
                'tipo' => $correlativo->tipoDocumento->description ?? 'Sin definir',
                'serie' => $correlativo->serie,
                'actual' => $correlativo->actual,
                'final' => $correlativo->final,
                'restantes' => $correlativo->numerosRestantes(),
                'estado' => $correlativo->estado_texto,
                'porcentaje_uso' => $correlativo->porcentajeUso()
            ];
        }));
    }
}
