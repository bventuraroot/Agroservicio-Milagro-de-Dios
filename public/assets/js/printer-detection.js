/**
 * Detector de impresoras disponibles
 * Para tickets de 80mm
 */

class PrinterDetector {
    constructor() {
        this.printers = [];
        this.selectedPrinter = null;
        this.init();
    }

    init() {
        console.log('PrinterDetector: Iniciando...');
        this.detectPrinters();
        this.createPrinterSelector();
        console.log('PrinterDetector: Inicializado con', this.printers.length, 'impresoras');
    }

    /**
     * Detectar impresoras disponibles
     */
    async detectPrinters() {
        try {
            // Método 1: Usar Web API si está disponible
            if ('getInstalledRelatedApps' in navigator) {
                const apps = await navigator.getInstalledRelatedApps();
                console.log('Apps instaladas:', apps);
            }

            // Método 2: Detectar impresoras a través de media queries
            this.detectPrintersByMedia();

            // Método 3: Mostrar información del navegador
            this.showBrowserPrintInfo();

        } catch (error) {
            console.warn('No se pudieron detectar impresoras automáticamente:', error);
            this.showManualPrinterSelection();
        }
    }

    /**
     * Detectar impresoras usando media queries
     */
    detectPrintersByMedia() {
        const mediaQueries = [
            { name: 'Impresora Genérica', query: 'print' },
            { name: 'Impresora Monocromática', query: 'print and (monochrome)' },
            { name: 'Impresora a Color', query: 'print and (color)' }
        ];

        mediaQueries.forEach(printer => {
            const mediaQuery = window.matchMedia(printer.query);
            if (mediaQuery.matches) {
                this.printers.push({
                    name: printer.name,
                    type: 'detected',
                    supported: true
                });
            }
        });
    }

    /**
     * Mostrar información del navegador sobre impresión
     */
    showBrowserPrintInfo() {
        const info = {
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language,
            cookieEnabled: navigator.cookieEnabled,
            onLine: navigator.onLine
        };

        console.log('Información del navegador para impresión:', info);

        // Detectar si es un dispositivo móvil
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

        if (isMobile) {
            this.showMobilePrintInfo();
        } else {
            this.showDesktopPrintInfo();
        }
    }

    /**
     * Información para dispositivos móviles
     */
    showMobilePrintInfo() {
        this.printers.push({
            name: 'Dispositivo Móvil',
            type: 'mobile',
            info: 'Usar la función de compartir del navegador para imprimir',
            supported: true
        });
    }

    /**
     * Información para escritorio
     */
    showDesktopPrintInfo() {
        // Impresoras comunes de 80mm
        const common80mmPrinters = [
            'Epson TM-T88',
            'Star TSP650',
            'Bixolon SRP-350',
            'Citizen CT-S310A',
            'POS-80 Series',
            'Impresora Térmica Generic'
        ];

        common80mmPrinters.forEach(printerName => {
            this.printers.push({
                name: printerName,
                type: '80mm',
                width: '80mm',
                recommended: true,
                supported: true
            });
        });
    }

    /**
     * Mostrar selección manual de impresoras
     */
    showManualPrinterSelection() {
        const commonPrinters = [
            'Impresora Predeterminada del Sistema',
            'Impresora Térmica 80mm',
            'Epson TM-T88',
            'Star TSP650',
            'Bixolon SRP-350',
            'Otra impresora'
        ];

        commonPrinters.forEach(printerName => {
            this.printers.push({
                name: printerName,
                type: 'manual',
                supported: true
            });
        });
    }

    /**
     * Crear selector de impresoras en la interfaz
     */
    createPrinterSelector() {
        const container = document.getElementById('printer-selector-container');
        if (!container) {
            console.warn('PrinterDetector: Container "printer-selector-container" no encontrado');
            return;
        }
        console.log('PrinterDetector: Creando selector con', this.printers.length, 'impresoras');

        const selectorHTML = `
            <div class="printer-selector mb-3">
                <label style="font-weight: bold; margin-bottom: 5px; display: block;">
                    🖨️ Impresora Seleccionada:
                </label>
                <select id="printer-select" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 8px;">
                    <option value="">Detectando impresoras...</option>
                </select>
                <small style="color: #666; font-size: 12px;">
                    Se detectará automáticamente la impresora predeterminada al imprimir
                </small>
            </div>
            <div class="printer-info">
                <button type="button" style="padding: 5px 10px; margin-right: 8px; border: 1px solid #17a2b8; background: white; color: #17a2b8; border-radius: 3px; cursor: pointer;" onclick="printerDetector.showPrinterInfo()">
                    ℹ️ Ver Información de Impresoras
                </button>
                <button type="button" style="padding: 5px 10px; border: 1px solid #28a745; background: white; color: #28a745; border-radius: 3px; cursor: pointer;" onclick="printerDetector.testPrint()">
                    🖨️ Prueba de Impresión
                </button>
            </div>
        `;

        // Ocultar fallback si existe
        const fallback = document.getElementById('printer-fallback');
        if (fallback) {
            fallback.style.display = 'none';
            console.log('PrinterDetector: Ocultando fallback');
        }

        container.innerHTML = selectorHTML;
        this.populatePrinterSelect();
    }

    /**
     * Llenar el selector con las impresoras detectadas
     */
    populatePrinterSelect() {
        const select = document.getElementById('printer-select');
        if (!select) {
            console.warn('PrinterDetector: Select "printer-select" no encontrado');
            return;
        }

        console.log('PrinterDetector: Poblando selector con', this.printers.length, 'impresoras');
        select.innerHTML = '<option value="">Seleccionar impresora...</option>';

        if (this.printers.length === 0) {
            console.log('PrinterDetector: No hay impresoras, agregando predeterminada');
            select.innerHTML += '<option value="default">Impresora Predeterminada del Sistema</option>';
        } else {
            this.printers.forEach((printer, index) => {
                const option = document.createElement('option');
                option.value = index;
                option.textContent = printer.name;
                if (printer.recommended) {
                    option.textContent += ' (Recomendada)';
                }
                select.appendChild(option);
                console.log('PrinterDetector: Agregada impresora:', printer.name);
            });
        }

        // Seleccionar la primera impresora por defecto
        if (this.printers.length > 0) {
            select.selectedIndex = 1;
            this.selectedPrinter = this.printers[0];
            console.log('PrinterDetector: Seleccionada por defecto:', this.selectedPrinter.name);
        }

        select.addEventListener('change', (e) => {
            const index = e.target.value;
            this.selectedPrinter = index !== '' ? this.printers[index] : null;
            console.log('PrinterDetector: Impresora cambiada a:', this.selectedPrinter ? this.selectedPrinter.name : 'Ninguna');
            this.updatePrinterInfo();
        });
    }

    /**
     * Actualizar información de la impresora seleccionada
     */
    updatePrinterInfo() {
        const infoDiv = document.getElementById('printer-info-display');
        if (!infoDiv || !this.selectedPrinter) return;

        infoDiv.innerHTML = `
            <div class="alert alert-info">
                <strong>Impresora:</strong> ${this.selectedPrinter.name}<br>
                ${this.selectedPrinter.width ? `<strong>Ancho:</strong> ${this.selectedPrinter.width}<br>` : ''}
                ${this.selectedPrinter.type ? `<strong>Tipo:</strong> ${this.selectedPrinter.type}<br>` : ''}
                ${this.selectedPrinter.info ? `<strong>Info:</strong> ${this.selectedPrinter.info}` : ''}
            </div>
        `;
    }

    /**
     * Mostrar información detallada de impresoras
     */
    showPrinterInfo() {
        let infoHTML = '<h6>Impresoras Disponibles:</h6><ul>';

        if (this.printers.length === 0) {
            infoHTML += '<li>No se detectaron impresoras específicas</li>';
            infoHTML += '<li>Se usará la impresora predeterminada del sistema</li>';
        } else {
            this.printers.forEach(printer => {
                infoHTML += `<li><strong>${printer.name}</strong>`;
                if (printer.type) infoHTML += ` (${printer.type})`;
                if (printer.width) infoHTML += ` - ${printer.width}`;
                if (printer.recommended) infoHTML += ' <span class="badge bg-success">Recomendada</span>';
                infoHTML += '</li>';
            });
        }

        infoHTML += '</ul>';
        infoHTML += '<p><strong>Nota:</strong> Para obtener mejores resultados con tickets de 80mm, asegúrate de:</p>';
        infoHTML += '<ul>';
        infoHTML += '<li>Configurar tu impresora térmica como predeterminada</li>';
        infoHTML += '<li>Establecer el ancho de papel a 80mm en las propiedades de la impresora</li>';
        infoHTML += '<li>Desactivar márgenes en las opciones de impresión</li>';
        infoHTML += '</ul>';

        // Mostrar en un modal o alert
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Información de Impresoras',
                html: infoHTML,
                icon: 'info',
                confirmButtonText: 'Entendido'
            });
        } else {
            alert('Ver consola para información detallada de impresoras');
            console.log(infoHTML);
        }
    }

    /**
     * Realizar prueba de impresión
     */
    testPrint() {
        const testContent = `
            <div style="width: 80mm; font-family: 'Courier New', monospace; font-size: 12px; text-align: center;">
                <h3>PRUEBA DE IMPRESIÓN</h3>
                <p>═══════════════════════════</p>
                <p>Impresora: ${this.selectedPrinter ? this.selectedPrinter.name : 'Predeterminada'}</p>
                <p>Fecha: ${new Date().toLocaleString()}</p>
                <p>═══════════════════════════</p>
                <p>Si este texto se ve bien<br>tu impresora está configurada<br>correctamente para tickets</p>
                <p>═══════════════════════════</p>
            </div>
        `;

        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Prueba de Impresión</title>
                    <style>
                        @page { width: 80mm; margin: 0; }
                        body { margin: 0; padding: 5mm; }
                    </style>
                </head>
                <body>
                    ${testContent}
                    <script>
                        window.onload = function() {
                            window.print();
                            setTimeout(function() { window.close(); }, 1000);
                        }
                    </script>
                </body>
            </html>
        `);
        printWindow.document.close();
    }

    /**
     * Obtener la impresora actualmente seleccionada
     */
    getSelectedPrinter() {
        return this.selectedPrinter;
    }

    /**
     * Obtener información de todas las impresoras
     */
    getAllPrinters() {
        return this.printers;
    }
}

// Inicializar detector global
let printerDetector;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    try {
        console.log('Inicializando PrinterDetector...');
        printerDetector = new PrinterDetector();

        // Verificación adicional después de 2 segundos
        setTimeout(function() {
            if (!printerDetector || printerDetector.printers.length === 0) {
                console.warn('PrinterDetector no se inicializó correctamente, forzando detección...');
                if (printerDetector) {
                    printerDetector.showManualPrinterSelection();
                    printerDetector.createPrinterSelector();
                }
            }
        }, 2000);

    } catch (error) {
        console.error('Error inicializando PrinterDetector:', error);
        // Mantener el fallback visible si hay error
    }
});

// Función global para acceder desde otros scripts
function getPrinterInfo() {
    return printerDetector ? printerDetector.getSelectedPrinter() : null;
}

function getAllPrinters() {
    return printerDetector ? printerDetector.getAllPrinters() : [];
}
