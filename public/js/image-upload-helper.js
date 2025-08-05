/**
 * Helper para mejorar la carga de imágenes
 * Incluye validaciones, preview, compresión y mejor UX
 */
class ImageUploadHelper {
    constructor(options = {}) {
        this.maxSize = options.maxSize || 5 * 1024 * 1024; // 5MB por defecto
        this.maxWidth = options.maxWidth || 1920;
        this.maxHeight = options.maxHeight || 1080;
        this.quality = options.quality || 0.8;
        this.allowedTypes = options.allowedTypes || ['image/jpeg', 'image/png', 'image/webp'];
        this.previewContainer = options.previewContainer || '#image-preview';
    }

    /**
     * Validar archivo antes de subir
     */
    validateFile(file) {
        const errors = [];

        // Validar tipo de archivo
        if (!this.allowedTypes.includes(file.type)) {
            errors.push(`Tipo de archivo no permitido. Solo se permiten: ${this.allowedTypes.join(', ')}`);
        }

        // Validar tamaño
        if (file.size > this.maxSize) {
            const maxSizeMB = (this.maxSize / (1024 * 1024)).toFixed(1);
            errors.push(`El archivo es muy grande. Máximo ${maxSizeMB}MB`);
        }

        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }

    /**
     * Mostrar preview de la imagen
     */
    showPreview(file, containerId = null) {
        const container = containerId ? document.getElementById(containerId) : document.querySelector(this.previewContainer);

        if (!container) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            container.innerHTML = `
                <div class="image-preview-wrapper">
                    <img src="${e.target.result}" class="rounded img-fluid" style="max-height: 200px;">
                    <div class="mt-2 image-info">
                        <small class="text-muted">
                            ${file.name} (${(file.size / 1024).toFixed(1)}KB)
                        </small>
                    </div>
                    <button type="button" class="mt-2 btn btn-sm btn-outline-danger" onclick="removePreview('${containerId || this.previewContainer}')">
                        <i class="fas fa-times"></i> Remover
                    </button>
                </div>
            `;
        }.bind(this);
        reader.readAsDataURL(file);
    }

    /**
     * Comprimir imagen antes de subir
     */
    compressImage(file) {
        return new Promise((resolve, reject) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();

            img.onload = function() {
                // Calcular nuevas dimensiones manteniendo proporción
                let { width, height } = img;

                if (width > this.maxWidth) {
                    height = (height * this.maxWidth) / width;
                    width = this.maxWidth;
                }

                if (height > this.maxHeight) {
                    width = (width * this.maxHeight) / height;
                    height = this.maxHeight;
                }

                canvas.width = width;
                canvas.height = height;

                // Dibujar imagen redimensionada
                ctx.drawImage(img, 0, 0, width, height);

                // Convertir a blob con calidad especificada
                canvas.toBlob((blob) => {
                    const compressedFile = new File([blob], file.name, {
                        type: file.type,
                        lastModified: Date.now()
                    });
                    resolve(compressedFile);
                }, file.type, this.quality);
            }.bind(this);

            img.onerror = reject;
            img.src = URL.createObjectURL(file);
        });
    }

    /**
     * Crear input de archivo mejorado
     */
    createEnhancedFileInput(options = {}) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = this.allowedTypes.join(',');
        input.style.display = 'none';

        // Agregar atributos adicionales
        if (options.multiple) input.multiple = true;
        if (options.capture) input.capture = options.capture;

        // Event listener para validación y preview
        input.addEventListener('change', async (e) => {
            const files = Array.from(e.target.files);

            for (let file of files) {
                // Validar archivo
                const validation = this.validateFile(file);
                if (!validation.isValid) {
                    this.showError(validation.errors.join('\n'));
                    continue;
                }

                // Mostrar preview
                this.showPreview(file, options.previewContainer);

                // Comprimir si es necesario
                if (file.size > 1024 * 1024) { // Si es mayor a 1MB
                    try {
                        const compressedFile = await this.compressImage(file);
                        // Reemplazar el archivo original con el comprimido
                        const dt = new DataTransfer();
                        dt.items.add(compressedFile);
                        input.files = dt.files;
                    } catch (error) {
                        console.error('Error al comprimir imagen:', error);
                    }
                }
            }
        });

        return input;
    }

    /**
     * Mostrar errores
     */
    showError(message) {
        // Crear notificación de error
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Insertar al inicio del body
        document.body.insertBefore(alert, document.body.firstChild);

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }

    /**
     * Mostrar progreso de carga
     */
    showUploadProgress(progress) {
        const progressBar = document.getElementById('upload-progress');
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
            progressBar.textContent = `${progress}%`;
        }
    }
}

/**
 * Función global para remover preview
 */
function removePreview(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = '';
    }
}

/**
 * Función para inicializar el helper
 */
function initImageUpload(options = {}) {
    return new ImageUploadHelper(options);
}

// Exportar para uso global
window.ImageUploadHelper = ImageUploadHelper;
window.initImageUpload = initImageUpload;
window.removePreview = removePreview;
