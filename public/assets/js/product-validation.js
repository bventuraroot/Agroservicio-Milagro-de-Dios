/**
 * Validaciones para formularios de productos
 */

class ProductValidator {
    constructor() {
        this.initializeValidations();
    }

    initializeValidations() {
        // Validación del formulario de crear producto
        this.setupFormValidation('#addproductForm');

        // Validación del formulario de editar producto
        this.setupFormValidation('#editproductForm');

        // Limpiar errores cuando el usuario empiece a escribir
        this.setupInputListeners();
    }

    setupFormValidation(formSelector) {
        const form = document.querySelector(formSelector);
        if (!form) return;

        form.addEventListener('submit', (e) => {
            if (!this.validateForm(form)) {
                e.preventDefault();
                return false;
            }
        });
    }

    validateForm(form) {
        let isValid = true;
        const errorMessages = [];
        const formId = form.id;

        // Validar código
        const codeField = form.querySelector('[name*="code"]');
        if (codeField && !this.validateRequiredField(codeField, 'El código del producto es obligatorio')) {
            isValid = false;
            errorMessages.push('El código del producto es obligatorio');
        }

        // Validar nombre
        const nameField = form.querySelector('[name*="name"]');
        if (nameField && !this.validateRequiredField(nameField, 'El nombre del producto es obligatorio')) {
            isValid = false;
            errorMessages.push('El nombre del producto es obligatorio');
        }

        // Validar descripción
        const descriptionField = form.querySelector('[name*="description"]');
        if (descriptionField && !this.validateRequiredField(descriptionField, 'La descripción del producto es obligatoria')) {
            isValid = false;
            errorMessages.push('La descripción del producto es obligatoria');
        }

        // Validar clasificación fiscal
        const cfiscalField = form.querySelector('[name*="cfiscal"]');
        if (cfiscalField && !this.validateSelectField(cfiscalField, 'Debe seleccionar una clasificación fiscal')) {
            isValid = false;
            errorMessages.push('Debe seleccionar una clasificación fiscal');
        }

        // Validar tipo
        const typeField = form.querySelector('[name*="type"]');
        if (typeField && !this.validateSelectField(typeField, 'Debe seleccionar un tipo')) {
            isValid = false;
            errorMessages.push('Debe seleccionar un tipo');
        }

        // Validar precio (solo en formulario de crear)
        if (formId === 'addproductForm') {
            const priceField = form.querySelector('[name="price"]');
            if (priceField && !this.validatePriceField(priceField)) {
                isValid = false;
                errorMessages.push('El precio debe ser un número válido mayor o igual a 0');
            }
        }

        if (!isValid) {
            this.showValidationErrors(errorMessages);
        }

        return isValid;
    }

    validateRequiredField(field, errorMessage) {
        const value = field.value.trim();
        if (!value) {
            this.markFieldAsInvalid(field, errorMessage);
            return false;
        }
        this.markFieldAsValid(field);
        return true;
    }

    validateSelectField(field, errorMessage) {
        const value = field.value;
        if (!value || value === 'Seleccione') {
            this.markFieldAsInvalid(field, errorMessage);
            return false;
        }
        this.markFieldAsValid(field);
        return true;
    }

    validatePriceField(field) {
        const value = field.value.trim();
        if (!value) {
            this.markFieldAsInvalid(field, 'El precio es obligatorio');
            return false;
        }

        const price = parseFloat(value);
        if (isNaN(price) || price < 0) {
            this.markFieldAsInvalid(field, 'El precio debe ser un número válido mayor o igual a 0');
            return false;
        }

        this.markFieldAsValid(field);
        return true;
    }

    markFieldAsInvalid(field, message) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');

        // Mostrar mensaje de error
        let errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback d-block';
            field.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    }

    markFieldAsValid(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');

        // Ocultar mensaje de error
        const errorDiv = field.parentNode.querySelector('.invalid-feedback.d-block');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    setupInputListeners() {
        // Limpiar clases de error cuando el usuario empiece a escribir
        document.addEventListener('input', (e) => {
            if (e.target.matches('input, textarea, select')) {
                e.target.classList.remove('is-invalid');
                e.target.classList.remove('is-valid');
            }
        });

        // Limpiar clases de error cuando el usuario cambie un select
        document.addEventListener('change', (e) => {
            if (e.target.matches('select')) {
                e.target.classList.remove('is-invalid');
                e.target.classList.remove('is-valid');
            }
        });
    }

    showValidationErrors(messages) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                html: messages.join('<br>'),
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#dc3545'
            });
        } else {
            // Fallback si SweetAlert no está disponible
            alert('Error de validación:\n' + messages.join('\n'));
        }
    }

    // Método para validar campos individuales en tiempo real
    validateField(field) {
        const fieldName = field.name;

        if (fieldName.includes('code') || fieldName.includes('name')) {
            return this.validateRequiredField(field, 'Este campo es obligatorio');
        }

        if (fieldName.includes('description')) {
            return this.validateRequiredField(field, 'La descripción es obligatoria');
        }

        if (fieldName.includes('cfiscal')) {
            return this.validateSelectField(field, 'Debe seleccionar una clasificación fiscal');
        }

        if (fieldName.includes('type')) {
            return this.validateSelectField(field, 'Debe seleccionar un tipo');
        }

        if (fieldName === 'price') {
            return this.validatePriceField(field);
        }

        return true;
    }
}

// Inicializar validaciones cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    new ProductValidator();
});

// Exportar para uso global si es necesario
window.ProductValidator = ProductValidator;
