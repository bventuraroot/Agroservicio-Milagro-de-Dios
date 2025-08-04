@extends('layouts.app')

@section('title', 'Crear Correlativo')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Crear Correlativo</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('correlativos.index') }}">Correlativos</a></li>
                        <li class="breadcrumb-item active">Crear</li>
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
                        <i class="fas fa-plus-circle"></i> Nuevo Correlativo
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('correlativos.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <!-- Información Básica -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_empresa" class="form-label">
                                        Empresa <span class="text-danger">*</span>
                                    </label>
                                    <select name="id_empresa" id="id_empresa" class="form-select @error('id_empresa') is-invalid @enderror" required>
                                        <option value="">Seleccione una empresa</option>
                                        @foreach($empresas as $empresa)
                                            <option value="{{ $empresa->id }}" {{ old('id_empresa') == $empresa->id ? 'selected' : '' }}>
                                                {{ $empresa->name }} ({{ $empresa->nit }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('id_empresa')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_tipo_doc" class="form-label">
                                        Tipo de Documento <span class="text-danger">*</span>
                                    </label>
                                    <select name="id_tipo_doc" id="id_tipo_doc" class="form-select @error('id_tipo_doc') is-invalid @enderror" required>
                                        <option value="">Seleccione un tipo</option>
                                        @foreach($tiposDocumento as $tipo)
                                            <option value="{{ $tipo->type }}" {{ old('id_tipo_doc') == $tipo->type ? 'selected' : '' }}>
                                                {{ $tipo->description }} ({{ $tipo->codemh }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('id_tipo_doc')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
                                           value="{{ old('serie') }}"
                                           maxlength="50"
                                           placeholder="Ej: A001"
                                           required>
                                    @error('serie')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Serie del correlativo (máximo 50 caracteres)</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="resolucion" class="form-label">Resolución</label>
                                    <input type="text"
                                           name="resolucion"
                                           id="resolucion"
                                           class="form-control @error('resolucion') is-invalid @enderror"
                                           value="{{ old('resolucion') }}"
                                           maxlength="50"
                                           placeholder="Número de resolución">
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
                                                   value="{{ old('inicial', 1) }}"
                                                   min="1"
                                                   required>
                                            @error('inicial')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
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
                                                   value="{{ old('final') }}"
                                                   min="1"
                                                   required>
                                            @error('final')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
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
                                                   value="{{ old('actual') }}"
                                                   min="1"
                                                   required>
                                            @error('actual')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">Próximo número a usar</div>
                                        </div>
                                    </div>
                                </div>

                                <div id="rangeInfo" class="alert alert-info" style="display: none;">
                                    <strong>Total de números disponibles:</strong> <span id="totalNumeros">0</span>
                                </div>
                            </div>
                        </div>

                        <!-- Configuración Avanzada -->
                        <div class="card border-secondary mb-3">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-cogs"></i> Configuración Avanzada (Opcional)
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
                                                   value="{{ old('clase_documento') }}"
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
                                                   value="{{ old('tipo_documento', '01') }}"
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
                                                <option value="00" {{ old('ambiente') == '00' ? 'selected' : '' }}>Pruebas (00)</option>
                                                <option value="01" {{ old('ambiente', '01') == '01' ? 'selected' : '' }}>Producción (01)</option>
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
                                                <option value="1" {{ old('tipogeneracion', '1') == '1' ? 'selected' : '' }}>Normal (1)</option>
                                                <option value="2" {{ old('tipogeneracion') == '2' ? 'selected' : '' }}>Contingencia (2)</option>
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
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Crear Correlativo
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
    const rangeInfo = document.getElementById('rangeInfo');
    const totalNumeros = document.getElementById('totalNumeros');

    function calcularRango() {
        const inicial = parseInt(inicialInput.value) || 0;
        const final = parseInt(finalInput.value) || 0;

        if (inicial > 0 && final > 0 && final >= inicial) {
            const total = final - inicial + 1;
            totalNumeros.textContent = total.toLocaleString();
            rangeInfo.style.display = 'block';

            // Auto-completar el número actual si está vacío
            if (!actualInput.value) {
                actualInput.value = inicial;
            }
        } else {
            rangeInfo.style.display = 'none';
        }
    }

    // Auto-completar número actual cuando cambie el inicial
    inicialInput.addEventListener('input', function() {
        if (this.value && !actualInput.value) {
            actualInput.value = this.value;
        }
        calcularRango();
    });

    finalInput.addEventListener('input', calcularRango);
    actualInput.addEventListener('input', calcularRango);

    // Validación en tiempo real
    finalInput.addEventListener('blur', function() {
        const inicial = parseInt(inicialInput.value) || 0;
        const final = parseInt(this.value) || 0;

        if (inicial > 0 && final > 0 && final < inicial) {
            this.setCustomValidity('El número final debe ser mayor o igual al inicial');
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

    // Calcular rango inicial si hay valores
    calcularRango();
});
</script>
@endpush
