/**
 * Detector de impresoras simplificado para tickets de 80mm
 */

// Variables globales
let printerDetector = null;

// Clase simplificada del detector
class SimplePrinterDetector {
    constructor() {
        this.printers = [];
        this.selectedPrinter = null;
        this.init();
    }

    init() {
        console.log('🖨️ Iniciando detector de impresoras simplificado...');
        this.addCommonPrinters();
        this.createSelector();
        console.log('✅ Detector inicializado con', this.printers.length, 'impresoras');
    }

    addCommonPrinters() {
        this.printers = [
            { name: 'Impresora Predeterminada del Sistema', type: 'default', recommended: false },
            { name: 'Epson TM-T88V', type: '80mm', recommended: true },
            { name: 'Epson TM-T88VI', type: '80mm', recommended: true },
            { name: 'Star TSP650II', type: '80mm', recommended: true },
            { name: 'Bixolon SRP-350plusIII', type: '80mm', recommended: true },
            { name: 'Citizen CT-S310A', type: '80mm', recommended: true },
            { name: 'POS-80 Series', type: '80mm', recommended: true },
            { name: 'Impresora Térmica Genérica', type: '80mm', recommended: false }
        ];
    }

    createSelector() {
        const container = document.getElementById('printer-selector-container');
        if (!container) {
            console.warn('❌ Container printer-selector-container no encontrado');
            return;
        }

        // Ocultar fallback
        const fallback = document.getElementById('printer-fallback');
        if (fallback) {
            fallback.style.display = 'none';
        }

        // Crear HTML del selector
        const html = `
            <div style="margin-bottom: 15px;">
                <label style="font-weight: bold; margin-bottom: 5px; display: block;">
                    🖨️ Impresora Seleccionada:
                </label>
                <select id="printer-select" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 8px;">
                    ${this.printers.map((printer, index) =>
                        `<option value="${index}">${printer.name}${printer.recommended ? ' (Recomendada)' : ''}</option>`
                    ).join('')}
                </select>
                <small style="color: #666; font-size: 12px;">
                    Configure su impresora de 80mm como predeterminada en el sistema
                </small>
            </div>
            <div style="margin-bottom: 10px;">
                <button type="button"
                        onclick="printerDetector.showInfo()"
                        style="padding: 5px 10px; margin-right: 8px; border: 1px solid #17a2b8; background: white; color: #17a2b8; border-radius: 3px; cursor: pointer;">
                    ℹ️ Información
                </button>
                <button type="button"
                        onclick="printerDetector.testPrint()"
                        style="padding: 5px 10px; border: 1px solid #28a745; background: white; color: #28a745; border-radius: 3px; cursor: pointer;">
                    🖨️ Prueba
                </button>
            </div>
        `;

        container.innerHTML = html;

        // Configurar evento de cambio
        const select = document.getElementById('printer-select');
        if (select) {
            select.selectedIndex = 1; // Seleccionar primera impresora recomendada
            this.selectedPrinter = this.printers[1];

            select.addEventListener('change', (e) => {
                const index = parseInt(e.target.value);
                this.selectedPrinter = this.printers[index];
                console.log('🔄 Impresora cambiada a:', this.selectedPrinter.name);
            });
        }
    }

    showInfo() {
        let message = '🖨️ IMPRESORAS DISPONIBLES:\n\n';
        this.printers.forEach((printer, index) => {
            message += `${index + 1}. ${printer.name}`;
            if (printer.recommended) message += ' ⭐';
            message += '\n';
        });

        message += '\n📋 CONSEJOS PARA IMPRESORAS DE 80MM:\n';
        message += '• Configure su impresora térmica como predeterminada\n';
        message += '• Establezca el ancho de papel a 80mm\n';
        message += '• Desactive los márgenes para aprovechar todo el papel\n';
        message += '• Use el driver específico del fabricante si está disponible';

        alert(message);
    }

    testPrint() {
        const testContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Prueba de Impresión</title>
                <style>
                    @page { width: 80mm; margin: 0; }
                    body { font-family: 'Courier New', monospace; font-size: 12px; margin: 5mm; text-align: center; }
                </style>
            </head>
            <body>
                <h3>PRUEBA DE IMPRESIÓN</h3>
                <p>═══════════════════════════</p>
                <p>Impresora: ${this.selectedPrinter ? this.selectedPrinter.name : 'Predeterminada'}</p>
                <p>Fecha: ${new Date().toLocaleString()}</p>
                <p>═══════════════════════════</p>
                <p>Si este texto se ve bien,<br>su impresora está configurada<br>correctamente para tickets</p>
                <p>═══════════════════════════</p>
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(function() { window.close(); }, 1000);
                    }
                </script>
            </body>
            </html>
        `;

        const printWindow = window.open('', '_blank');
        printWindow.document.write(testContent);
        printWindow.document.close();
    }

    getSelectedPrinter() {
        return this.selectedPrinter;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando detector de impresoras...');
    try {
        printerDetector = new SimplePrinterDetector();
    } catch (error) {
        console.error('❌ Error inicializando detector:', error);
    }
});

// Funciones globales para compatibilidad
function getPrinterInfo() {
    return printerDetector ? printerDetector.getSelectedPrinter() : { name: 'Impresora Predeterminada' };
}

function getAllPrinters() {
    return printerDetector ? printerDetector.printers : [];
}

console.log('📁 Archivo printer-detection-simple.js cargado');
