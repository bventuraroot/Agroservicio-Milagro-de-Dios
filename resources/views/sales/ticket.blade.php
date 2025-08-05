<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Venta #{{ $sale->id }}</title>
    <!-- SweetAlert2 para notificaciones -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @media print {
            @page {
                width: 80mm;
                height: auto;
                margin: 0;
                margin-top: 0;
                margin-bottom: 0;
                margin-left: 0;
                margin-right: 0;
                size: 80mm auto;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                margin: 0;
                padding: 0;
                font-size: 16px !important;
                background: white !important;
                -webkit-print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            /* Ocultar cualquier control del navegador */
            @page {
                size: 80mm auto;
                margin: 0;
            }
        }

        body {
            font-family: 'Courier New', monospace;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
            font-size: 16px;
            line-height: 1.4;
        }

        .ticket-container {
            width: 100%;
        }

        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .company-name {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 3px;
        }

        .company-info {
            font-size: 12px;
            line-height: 1.2;
        }

        .sale-info {
            margin-bottom: 10px;
            font-size: 14px;
        }

        .sale-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .products {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 8px 0;
            margin: 10px 0;
        }

        .product-header {
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .product-item {
            margin-bottom: 5px;
            font-size: 11px;
        }

        .product-name {
            font-weight: bold;
            margin-bottom: 1px;
        }

        .product-details {
            display: flex;
            justify-content: space-between;
        }

        .totals {
            margin-top: 10px;
            font-size: 12px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .total-final {
            font-weight: bold;
            font-size: 13px;
            border-top: 1px solid #000;
            padding-top: 3px;
            margin-top: 5px;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 11px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }

        .print-button {
            margin: 20px auto;
            display: block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .print-button:hover {
            background-color: #0056b3;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .d-flex {
            display: flex;
        }

        .align-items-center {
            align-items: center;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        .badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }

        .bg-warning {
            background-color: #ffc107;
            color: #000;
        }

        .bg-success {
            background-color: #28a745;
        }

        .mb-3 {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
        <!-- Botón de impresión (no se imprime) -->
    <div class="no-print">
        <!-- Contenedor para selector de impresoras -->
        <div id="printer-selector-container" class="mb-3">
            <!-- Fallback si el JavaScript no carga -->
            <div id="printer-fallback" style="display: block;">
                <label style="font-weight: bold; margin-bottom: 5px; display: block;">
                    🖨️ Impresora:
                </label>
                <select style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 8px;">
                    <option>Impresora Predeterminada del Sistema</option>
                    <option>Epson TM-T88V (Recomendada)</option>
                    <option>Star TSP650 (Recomendada)</option>
                    <option>Bixolon SRP-350 (Recomendada)</option>
                    <option>Citizen CT-S310A (Recomendada)</option>
                    <option>POS-80 Series (Recomendada)</option>
                </select>
                <small style="color: #666; font-size: 12px;">
                    Configure su impresora de 80mm como predeterminada en el sistema
                </small>
            </div>
        </div>

        <!-- Información de impresora seleccionada -->
        <div id="printer-info-display"></div>

        <div class="gap-2 d-flex align-items-center">
            <button class="print-button" onclick="window.print()">
                🖨️ Imprimir Ticket
            </button>
            @if(!$autoprint)
                <span class="badge bg-warning">Modo Vista</span>
            @else
                <span class="badge bg-success">Auto-Impresión</span>
            @endif
        </div>
    </div>

    <div class="ticket-container">
        <!-- Encabezado -->
        <div class="header">
            <div class="company-name">{{ $sale->company->name ?? 'EMPRESA' }}</div>
            <div class="company-info">
                @if($sale->company->address)
                    {{ $sale->company->address }}<br>
                @endif
                @if($sale->company->phone)
                    Tel: {{ $sale->company->phone }}<br>
                @endif
                @if($sale->company->email)
                    {{ $sale->company->email }}
                @endif
            </div>
        </div>

        <!-- Información de la venta -->
        <div class="sale-info">
            <div class="sale-info-row">
                <span><strong>Ticket #:</strong></span>
                <span>{{ $sale->id }}</span>
            </div>
            <div class="sale-info-row">
                <span><strong>Fecha:</strong></span>
                <span>{{ \Carbon\Carbon::parse($sale->date)->format('d/m/Y H:i') }}</span>
            </div>
            <div class="sale-info-row">
                <span><strong>Tipo:</strong></span>
                <span>{{ $sale->typedocument->description ?? 'Venta' }}</span>
            </div>
            <div class="sale-info-row">
                <span><strong>Cliente:</strong></span>
                <span>
                    @if($sale->client)
                        @if($sale->client->tpersona == 'N')
                            {{ $sale->client->firstname }} {{ $sale->client->firstlastname }}
                        @else
                            {{ substr($sale->client->name_contribuyente, 0, 25) }}
                        @endif
                    @else
                        Venta al menudeo
                    @endif
                </span>
            </div>
            <div class="sale-info-row">
                <span><strong>Pago:</strong></span>
                <span>
                    @switch($sale->waytopay)
                        @case(1) CONTADO @break
                        @case(2) CRÉDITO @break
                        @case(3) OTRO @break
                        @default CONTADO
                    @endswitch
                </span>
            </div>
        </div>

        <!-- Productos -->
        <div class="products">
            <div class="text-center product-header">
                PRODUCTOS
            </div>

            @foreach($sale->details as $detail)
                <div class="product-item">
                    <div class="product-name">
                        {{ $detail->product->name ?? 'Producto' }} {{ $detail->product->marca->name ?? '' }}
                    </div>
                    <div class="product-details">
                        <span>{{ $detail->amountp }} x ${{ number_format($detail->priceunit, 2) }}</span>
                        <span class="text-right">${{ number_format($detail->pricesale + $detail->nosujeta + $detail->exempt, 2) }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Totales -->
        <div class="totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span class="text-right">${{ number_format($subtotal, 2) }}</span>
            </div>

            @if($totalIva > 0)
                <div class="total-row">
                    <span>IVA (13%):</span>
                    <span class="text-right">${{ number_format($totalIva, 2) }}</span>
                </div>
            @endif

            <div class="total-row total-final">
                <span>TOTAL:</span>
                <span class="text-right">${{ number_format($total, 2) }}</span>
            </div>
        </div>

        <!-- Pie del ticket -->
        <div class="footer">
            <div>¡Gracias por su compra!</div>
            <div style="margin-top: 5px; font-size: 9px;">
                {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}
            </div>
            @if($sale->acuenta && $sale->acuenta != 'Venta al menudeo')
                <div style="margin-top: 5px; font-size: 9px;">
                    {{ $sale->acuenta }}
                </div>
            @endif
        </div>
    </div>

            <!-- Script para detección de impresoras (versión simplificada) -->
    <script src="{{ asset('assets/js/printer-detection-simple.js') }}"></script>

    <!-- Script de verificación -->
    <script>
        // Verificar que el archivo se cargó
        setTimeout(function() {
            if (typeof printerDetector === 'undefined' || !printerDetector) {
                console.warn('⚠️ PrinterDetector no se cargó, usando fallback');

                // Activar funcionalidad del fallback
                const fallback = document.getElementById('printer-fallback');
                if (fallback) {
                    fallback.style.display = 'block';
                    const fallbackSelect = fallback.querySelector('select');
                    if (fallbackSelect) {
                        fallbackSelect.addEventListener('change', function() {
                            console.log('🖨️ Impresora seleccionada (fallback):', this.value);
                        });
                    }
                }
            } else {
                console.log('✅ PrinterDetector cargado y funcionando');
            }
        }, 1000);
    </script>

        <script>
        let hasAutoprinted = false;

        // Función mejorada de impresión con información de impresora
        function printWithPrinterInfo() {
            const selectedPrinter = getPrinterInfo();

            if (selectedPrinter) {
                console.log('Imprimiendo con:', selectedPrinter.name);

                // Mostrar información antes de imprimir (solo si no es auto-impresión)
                if (!hasAutoprinted && typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Imprimiendo Ticket',
                        text: `Enviando a: ${selectedPrinter.name}`,
                        icon: 'info',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            }

            // Imprimir
            window.print();
        }

                // Auto-imprimir al cargar solo si está habilitado
        const autoprint = {{ $autoprint ? 'true' : 'false' }};
        const autoClose = new URLSearchParams(window.location.search).get('auto_close') === 'true';

        // Función de impresión silenciosa
        function silentPrint() {
            try {
                // Método 1: Intentar imprimir sin diálogo usando configuraciones específicas
                const printSettings = {
                    silent: true,
                    printBackground: true,
                    paperWidth: 80, // 80mm
                    paperHeight: 297, // Largo estándar
                    marginsType: 1, // Sin márgenes
                    shouldPrintBackgrounds: true,
                    shouldPrintSelectionOnly: false
                };

                // Si está disponible la API de impresión del navegador
                if (window.navigator && window.navigator.printing) {
                    window.navigator.printing.print(printSettings);
                    return true;
                }

                // Método 2: Usar execCommand si está disponible (funciona en algunos navegadores)
                if (document.execCommand) {
                    try {
                        document.execCommand('print', false, null);
                        return true;
                    } catch (e) {
                        console.log('execCommand no disponible:', e);
                    }
                }

                // Método 3: window.print() estándar con configuraciones CSS optimizadas
                window.print();
                return true;

            } catch (error) {
                console.error('Error en impresión silenciosa:', error);
                // Fallback a impresión normal
                window.print();
                return false;
            }
        }

        window.addEventListener('load', function() {
            if (autoprint) {
                setTimeout(function() {
                    if (!hasAutoprinted) {
                        hasAutoprinted = true;
                        console.log('Auto-imprimiendo ticket...');

                        // Si es auto-close, usar impresión silenciosa
                        if (autoClose) {
                            silentPrint();
                        } else {
                            printWithPrinterInfo();
                        }
                    }
                }, 500); // Reducir tiempo para impresión más rápida
            } else {
                console.log('Auto-impresión deshabilitada. Usa el botón para imprimir.');
            }
        });

        // Cerrar ventana después de imprimir si está habilitado auto_close
        window.addEventListener('afterprint', function() {
            if (autoClose) {
                console.log('Cerrando ventana automáticamente...');
                setTimeout(function() {
                    window.close();
                }, 1000);
            }
        });

        // Configurar el botón de impresión manual
        document.addEventListener('DOMContentLoaded', function() {
            const printButton = document.querySelector('.print-button');
            if (printButton) {
                printButton.onclick = function() {
                    hasAutoprinted = false; // Permitir mostrar notificación en impresión manual
                    printWithPrinterInfo();
                };
            }

            // Agregar atajo de teclado Ctrl+P
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'p') {
                    e.preventDefault();
                    hasAutoprinted = false;
                    printWithPrinterInfo();
                }
            });
        });

        // Función para imprimir desde el enlace padre (si se llama desde otra ventana)
        function triggerPrint() {
            hasAutoprinted = false;
            printWithPrinterInfo();
        }

        // Hacer la función disponible globalmente
        window.triggerPrint = triggerPrint;
    </script>
</body>
</html>
