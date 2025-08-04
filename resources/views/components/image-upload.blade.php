@props([
    'name' => 'image',
    'label' => 'Imagen',
    'required' => false,
    'multiple' => false,
    'accept' => 'image/*',
    'maxSize' => '5MB',
    'preview' => true,
    'previewId' => 'image-preview',
    'currentImage' => null,
    'currentImageUrl' => null
])

<div class="image-upload-component">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <div class="image-upload-area" id="upload-area-{{ $name }}">
        <input
            type="file"
            name="{{ $name }}"
            id="{{ $name }}"
            class="form-control @error($name) is-invalid @enderror"
            accept="{{ $accept }}"
            @if($multiple) multiple @endif
            @if($required) required @endif
            style="display: none;"
        >

        <div class="upload-placeholder" id="upload-trigger-{{ $name }}">
            <div class="upload-icon">
                <i class="fas fa-cloud-upload-alt fa-3x text-muted"></i>
            </div>
            <div class="upload-text">
                <p class="mb-1">Haz clic para seleccionar una imagen</p>
                <small class="text-muted">
                    Formatos: JPG, PNG, WEBP, GIF | Máximo: {{ $maxSize }}
                </small>
            </div>
        </div>

        @if($currentImage && $currentImageUrl)
            <div class="current-image mt-3">
                <img src="{{ $currentImageUrl }}" alt="Imagen actual" class="img-thumbnail" style="max-height: 150px;">
                <div class="mt-2">
                    <small class="text-muted">Imagen actual: {{ $currentImage }}</small>
                </div>
            </div>
        @endif
    </div>

    @if($preview)
        <div id="{{ $previewId }}" class="image-preview mt-3"></div>
    @endif

    <!-- Barra de progreso -->
    <div class="progress mt-2" id="progress-{{ $name }}" style="display: none;">
        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
    </div>

    @error($name)
        <div class="invalid-feedback d-block">
            {{ $message }}
        </div>
    @enderror

    <div class="upload-help mt-2">
        <small class="text-muted">
            <i class="fas fa-info-circle"></i>
            La imagen será optimizada automáticamente para mejor rendimiento.
        </small>
    </div>
</div>

<style>
.image-upload-component {
    margin-bottom: 1rem;
}

.image-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.image-upload-area:hover {
    border-color: #007bff;
    background-color: #f8f9fa;
}

.image-upload-area.dragover {
    border-color: #28a745;
    background-color: #d4edda;
}

.upload-placeholder {
    cursor: pointer;
    transition: all 0.2s ease;
}

.upload-placeholder:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.upload-icon {
    margin-bottom: 1rem;
}

.upload-text p {
    font-weight: 500;
    color: #495057;
}

.image-preview {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    background-color: #f8f9fa;
}

.image-preview-wrapper {
    text-align: center;
}

.image-preview img {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.image-info {
    font-size: 0.875rem;
}

.progress {
    height: 8px;
    border-radius: 4px;
}

.upload-help {
    font-size: 0.875rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('{{ $name }}');
    const uploadArea = document.getElementById('upload-area-{{ $name }}');
    const uploadTrigger = document.getElementById('upload-trigger-{{ $name }}');
    const progressBar = document.getElementById('progress-{{ $name }}');
    const progressBarInner = progressBar ? progressBar.querySelector('.progress-bar') : null;

    // Hacer click en el área para abrir el selector de archivos
    if (uploadTrigger) {
        uploadTrigger.addEventListener('click', function() {
            input.click();
        });
    }

    // Fallback: también hacer click en toda el área de upload
    if (uploadArea) {
        uploadArea.addEventListener('click', function(e) {
            // Solo si no se hizo click en el input directamente
            if (e.target !== input) {
                input.click();
            }
        });
    }

    // Drag and drop functionality
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            input.files = files;
            handleFileSelect(files[0]);
        }
    });

    // File selection
    input.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            handleFileSelect(e.target.files[0]);
        }
    });

    function handleFileSelect(file) {
        // Validar archivo
        const validation = validateFile(file);
        if (!validation.isValid) {
            showError(validation.errors.join('\n'));
            return;
        }

        // Mostrar preview
        @if($preview)
            showPreview(file, '{{ $previewId }}');
        @endif

        // Simular progreso de carga
        simulateUploadProgress();
    }

    function validateFile(file) {
        const errors = [];
        const maxSize = {{ str_replace(['MB', 'KB'], ['*1024*1024', '*1024'], $maxSize) }};
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if (!allowedTypes.includes(file.type)) {
            errors.push('Tipo de archivo no permitido');
        }

        if (file.size > maxSize) {
            errors.push(`El archivo es muy grande. Máximo ${@json($maxSize)}`);
        }

        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }

    function showPreview(file, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            container.innerHTML = `
                <div class="image-preview-wrapper">
                    <img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;">
                    <div class="image-info mt-2">
                        <small class="text-muted">
                            ${file.name} (${(file.size / 1024).toFixed(1)}KB)
                        </small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removePreview('${containerId}')">
                        <i class="fas fa-times"></i> Remover
                    </button>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }

    function simulateUploadProgress() {
        if (!progressBar) return;

        progressBar.style.display = 'block';
        let progress = 0;

        const interval = setInterval(() => {
            progress += Math.random() * 30;
            if (progress >= 100) {
                progress = 100;
                clearInterval(interval);
                setTimeout(() => {
                    progressBar.style.display = 'none';
                }, 1000);
            }

            progressBarInner.style.width = progress + '%';
            progressBarInner.textContent = Math.round(progress) + '%';
        }, 200);
    }

    function showError(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.insertBefore(alert, document.body.firstChild);

        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
});

function removePreview(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = '';
    }
}
</script>
