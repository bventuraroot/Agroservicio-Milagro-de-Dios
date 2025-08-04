@props([
    'name' => 'image',
    'label' => 'Imagen',
    'required' => false,
    'currentImage' => null,
    'currentImageUrl' => null
])

<div class="simple-image-upload">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <div class="image-upload-container">
        <!-- Input de archivo oculto -->
        <input
            type="file"
            name="{{ $name }}"
            id="{{ $name }}"
            class="form-control @error($name) is-invalid @enderror"
            accept="image/*"
            @if($required) required @endif
            style="display: none;"
        >

        <!-- Área clickeable -->
        <div class="upload-area" id="upload-area-{{ $name }}">
            <div class="upload-content">
                <i class="fas fa-cloud-upload-alt fa-2x text-primary mb-2"></i>
                <p class="mb-1">Haz clic para seleccionar una imagen</p>
                <small class="text-muted">JPG, PNG, WEBP, GIF - Máximo 5MB</small>
            </div>
        </div>

        <!-- Preview de imagen actual (solo si existe) -->
        @if($currentImage && $currentImageUrl)
            <div class="current-image mt-3" id="current-image-{{ $name }}">
                <img src="{{ $currentImageUrl }}" alt="Imagen actual" class="img-thumbnail" style="max-height: 100px;">
                <div class="mt-1">
                    <small class="text-muted">{{ $currentImage }}</small>
                </div>
            </div>
        @endif

        <!-- Preview de nueva imagen -->
        <div id="preview-{{ $name }}" class="image-preview mt-3" style="display: none;">
            <img id="preview-img-{{ $name }}" src="" alt="Preview" class="img-thumbnail" style="max-height: 150px;">
            <div class="mt-2">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePreview('{{ $name }}')">
                    <i class="fas fa-times"></i> Remover
                </button>
            </div>
        </div>
    </div>

    @error($name)
        <div class="invalid-feedback d-block">
            {{ $message }}
        </div>
    @enderror
</div>

<style>
.simple-image-upload {
    margin-bottom: 1rem;
}

.image-upload-container {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
}

.image-upload-container:hover {
    border-color: #007bff;
    background-color: #e3f2fd;
}

.upload-area {
    cursor: pointer;
    padding: 1rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.upload-area:hover {
    background-color: rgba(0, 123, 255, 0.1);
}

.upload-content {
    pointer-events: none;
}

.image-preview {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 1rem;
    background-color: white;
}

.current-image {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 1rem;
    background-color: white;
}
</style>

<script>
// Función para inicializar el componente
function initializeImageUpload(componentId) {
    const input = document.getElementById(componentId);
    const uploadArea = document.getElementById('upload-area-' + componentId);
    const previewContainer = document.getElementById('preview-' + componentId);
    const previewImg = document.getElementById('preview-img-' + componentId);
    const currentImage = document.getElementById('current-image-' + componentId);

    if (!input || !uploadArea) {
        return;
    }

    // Verificar si ya se han registrado los eventos para este componente
    if (input.dataset.initialized === 'true') {
        return;
    }

    // Marcar como inicializado
    input.dataset.initialized = 'true';

    // Hacer click en el área para abrir el selector
    uploadArea.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        input.click();
    });

    // Manejar selección de archivo
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validar archivo
            if (!validateFile(file)) {
                return;
            }
            // Mostrar preview y ocultar imagen actual
            showPreview(file);
        }
    });

    function validateFile(file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if (!allowedTypes.includes(file.type)) {
            alert('Tipo de archivo no permitido. Solo se permiten imágenes.');
            input.value = '';
            return false;
        }

        if (file.size > maxSize) {
            alert('El archivo es muy grande. Máximo 5MB.');
            input.value = '';
            return false;
        }

        return true;
    }

    function showPreview(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (previewImg) {
                previewImg.src = e.target.result;
            }
            if (previewContainer) {
                previewContainer.style.display = 'block';
            }
            // Ocultar imagen actual si existe
            if (currentImage) {
                currentImage.style.display = 'none';
            }
        };
        reader.readAsDataURL(file);
    }
}

// Función global para remover preview
function removePreview(name) {
    const input = document.getElementById(name);
    const previewContainer = document.getElementById('preview-' + name);
    const currentImage = document.getElementById('current-image-' + name);

    if (input) {
        input.value = '';
        // Resetear el estado de inicialización para permitir nueva selección
        input.dataset.initialized = 'false';

        // Remover todos los event listeners existentes
        const newInput = input.cloneNode(true);
        input.parentNode.replaceChild(newInput, input);
    }

    if (previewContainer) {
        previewContainer.style.display = 'none';
    }
    // Volver a mostrar la imagen actual si existe
    if (currentImage) {
        currentImage.style.display = 'block';
    }
}

// Función global para limpiar completamente el componente
function clearImageUpload(name) {
    const input = document.getElementById(name);
    const previewContainer = document.getElementById('preview-' + name);
    const previewImg = document.getElementById('preview-img-' + name);
    const currentImage = document.getElementById('current-image-' + name);

    if (input) {
        input.value = '';
        // Resetear el estado de inicialización
        input.dataset.initialized = 'false';

        // Remover todos los event listeners existentes
        const newInput = input.cloneNode(true);
        input.parentNode.replaceChild(newInput, input);
    }

    if (previewContainer) {
        previewContainer.style.display = 'none';
    }

    if (previewImg) {
        previewImg.src = '';
    }

    // Volver a mostrar la imagen actual si existe
    if (currentImage) {
        currentImage.style.display = 'block';
    }
}
</script>
