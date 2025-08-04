@extends('layouts.app')

@section('title', 'Editar Correlativo')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Editar Correlativo #{{ $correlativo->id }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('correlativos.index') }}">Correlativos</a></li>
                        <li class="breadcrumb-item active">Editar</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit"></i> Editar Correlativo
                    </h5>
                </div>
                <div class="card-body">
                    @if($correlativo->actual > $correlativo->inicial)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Atención:</strong> Este correlativo ya ha sido utilizado.
                            Tenga cuidado al modificar los rangos para no generar duplicados.
                        </div>
                    @endif

                    <form action="{{ route('correlativos.update', $correlativo->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Información Básica (Solo lectura para campos críticos) -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Empresa</label>
                                    <input type="text"
                                           class="form-control"
                                           value="{{ $correlativo->empresa->name ?? 'N/A' }} ({{ $correlativo->empresa->nit ?? 'N/A' }})"
                                           readonly>
                                    <div class="form-text">No se puede cambiar la empresa una vez creado el correlativo</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Documento</label>
                                    <input type="text"
                                           class="form-control"
                                           value="{{ $correlativo->tipoDocumento->description ?? 'N/A' }} ({{ $correlativo->tipoDocumento->codemh ?? 'N/A' }})"
                                           readonly>
                                    <div class="form-text">No se puede cambiar el tipo de documento una vez creado</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="serie" class="form-label">
                                        Serie <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           name="serie"
                                           id="serie"
                                           class="form-control @error('serie') is-invalid @enderror"
                                           value="{{ old('serie', $correlativo->serie) }}"
                                           maxlength="50"
                                           required>
                                    @error('serie')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="resolucion" class="form-label">Resolución</label>
                                    <input type="text"
                                           name="resolucion"
                                           id="resolucion"
                                           class="form-control @error('resolucion') is-invalid @enderror"
                                           value="{{ old('resolucion', $correlativo->resolucion) }}"
                                           maxlength="50">
                                    @error('resolucion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Rango de Números -->
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-sort-numeric-up"></i> Rango de Numeración
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="inicial" class="form-label">
                                                Número Inicial <span class="text-danger">*</span>
                                            </label>
                                            <input type="number"
                                                   name="inicial"
                                                   id="inicial"
                                                   class="form-control @error('inicial') is-invalid @enderror"
                                                   value="{{ old('inicial', $correlativo->inicial) }}"
                                                   min="1"
                                                   {{ $correlativo->actual > $correlativo->inicial ? 'readonly' : '' }}
                                                   required>
                                            @error('inicial')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            @if($correlativo->actual > $correlativo->inicial)
                                                <div class="form-text text-warning">
                                                    No modificable: ya se han usado números
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="final" class="form-label">
                                                Número Final <span class="text-danger">*</span>
                                            </label>
                                            <input type="number"
                                                   name="final"
                                                   id="final"
                                                   class="form-control @error('final') is-invalid @enderror"
                                                   value="{{ old('final', $correlativo->final) }}"
                                                   min="{{ $correlativo->actual }}"
                                                   required>
                                            @error('final')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                Mínimo: {{ number_format($correlativo->actual) }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="actual" class="form-label">
                                                Número Actual <span class="text-danger">*</span>
                                            </label>
                                            <input type="number"
                                                   name="actual"
                                                   id="actual"
                                                   class="form-control @error('actual') is-invalid @enderror"
                                                   value="{{ old('actual', $correlativo->actual) }}"
                                                   min="{{ $correlativo->inicial }}"
                                                   required>
                                            @error('actual')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                Próximo número a usar (min: {{ number_format($correlativo->inicial) }})
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="alert alert-info">
                                            <strong>Números usados:</strong> {{ number_format($correlativo->actual - $correlativo->inicial) }}<br>
                                            <strong>Números restantes:</strong> {{ number_format($correlativo->numerosRestantes()) }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div id="rangeInfo" class="alert alert-success">
                                            <strong>Total disponibles:</strong> <span id="totalNumeros">{{ number_format($correlativo->final - $correlativo->inicial + 1) }}</span><br>
                                            <strong>Porcentaje usado:</strong> <span id="porcentajeUso">{{ number_format($correlativo->porcentajeUso(), 1) }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="card border-info mb-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-toggle-on"></i> Estado del Correlativo
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="estado" class="form-label">Estado</label>
                                            <select name="estado" id="estado" class="form-select @error('estado') is-invalid @enderror">
                                                <option value="1" {{ old('estado', $correlativo->estado) == 1 ? 'selected' : '' }}>Activo</option>
                                                <option value="0" {{ old('estado', $correlativo->estado) == 0 ? 'selected' : '' }}>Inactivo</option>
                                                @if($correlativo->estado == 2)
                                                    <option value="2" selected>Agotado</option>
                                                @endif
                                                <option value="3" {{ old('estado', $correlativo->estado) == 3 ? 'selected' : '' }}>Suspendido</option>
                                            </select>
                                            @error('estado')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Estado Actual</label>
                                            <div class="form-control-plaintext">
                                                {!! $correlativo->estado_badge !!}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Configuración Avanzada -->
                        <div class="card border-secondary mb-3">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-cogs"></i> Configuración Avanzada
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="clase_documento" class="form-label">Clase Documento</label>
                                            <input type="text"
                                                   name="clase_documento"
                                                   id="clase_documento"
                                                   class="form-control @error('clase_documento') is-invalid @enderror"
                                                   value="{{ old('clase_documento', $correlativo->clase_documento) }}"
                                                   maxlength="1">
                                            @error('clase_documento')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="tipo_documento" class="form-label">Tipo</label>
                                            <input type="text"
                                                   name="tipo_documento"
                                                   id="tipo_documento"
                                                   class="form-control @error('tipo_documento') is-invalid @enderror"
                                                   value="{{ old('tipo_documento', $correlativo->tipo_documento) }}"
                                                   maxlength="2">
                                            @error('tipo_documento')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="ambiente" class="form-label">Ambiente</label>
                                            <select name="ambiente" id="ambiente" class="form-select @error('ambiente') is-invalid @enderror">
                                                <option value="">Seleccionar</option>
                                                <option value="00" {{ old('ambiente', $correlativo->ambiente) == '00' ? 'selected' : '' }}>Pruebas (00)</option>
                                                <option value="01" {{ old('ambiente', $correlativo->ambiente) == '01' ? 'selected' : '' }}>Producción (01)</option>
                                            </select>
                                            @error('ambiente')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="tipogeneracion" class="form-label">Tipo Generación</label>
                                            <select name="tipogeneracion" id="tipogeneracion" class="form-select @error('tipogeneracion') is-invalid @enderror">
                                                <option value="">Seleccionar</option>
                                                <option value="1" {{ old('tipogeneracion', $correlativo->tipogeneracion) == '1' ? 'selected' : '' }}>Normal (1)</option>
                                                <option value="2" {{ old('tipogeneracion', $correlativo->tipogeneracion) == '2' ? 'selected' : '' }}>Contingencia (2)</option>
                                            </select>
                                            @error('tipogeneracion')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('correlativos.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Correlativo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inicialInput = document.getElementById('inicial');
    const finalInput = document.getElementById('final');
    const actualInput = document.getElementById('actual');
    const totalNumeros = document.getElementById('totalNumeros');
    const porcentajeUso = document.getElementById('porcentajeUso');

    function calcularEstadisticas() {
        const inicial = parseInt(inicialInput.value) || 0;
        const final = parseInt(finalInput.value) || 0;
        const actual = parseInt(actualInput.value) || 0;

        if (inicial > 0 && final > 0 && final >= inicial) {
            const total = final - inicial + 1;
            const usados = actual - inicial;
            const porcentaje = total > 0 ? (usados / total) * 100 : 0;

            totalNumeros.textContent = total.toLocaleString();
            porcentajeUso.textContent = porcentaje.toFixed(1) + '%';
        }
    }

    // Eventos para recalcular estadísticas
    [inicialInput, finalInput, actualInput].forEach(input => {
        input.addEventListener('input', calcularEstadisticas);
    });

    // Validación en tiempo real
    finalInput.addEventListener('blur', function() {
        const actual = parseInt(actualInput.value) || 0;
        const final = parseInt(this.value) || 0;

        if (actual > 0 && final > 0 && final < actual) {
            this.setCustomValidity('El número final debe ser mayor o igual al actual');
            this.reportValidity();
        } else {
            this.setCustomValidity('');
        }
    });

    actualInput.addEventListener('blur', function() {
        const inicial = parseInt(inicialInput.value) || 0;
        const final = parseInt(finalInput.value) || 0;
        const actual = parseInt(this.value) || 0;

        if (inicial > 0 && final > 0 && actual > 0) {
            if (actual < inicial || actual > final) {
                this.setCustomValidity('El número actual debe estar entre el inicial y final');
                this.reportValidity();
            } else {
                this.setCustomValidity('');
            }
        }
    });

    // Calcular estadísticas iniciales
    calcularEstadisticas();
});
</script>
@endpush
