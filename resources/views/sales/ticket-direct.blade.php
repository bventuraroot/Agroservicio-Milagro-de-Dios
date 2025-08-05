<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=80mm, initial-scale=1.0">
    <title>Ticket #{{ $sale->id }}</title>
    <style>
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
                padding: 0;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Consolas, monospace;
            font-size: 14px;
            line-height: 1.3;
            width: 80mm;
            margin: 0;
            padding: 2mm;
            background: white;
            color: black;
        }

        .header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
        }

        .company-name {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 2px;
        }

        .company-info {
            font-size: 12px;
        }

        .sale-info {
            margin: 8px 0;
            font-size: 13px;
        }

        .sale-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1px;
        }

        .products {
            margin: 8px 0;
        }

        .product-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 5px;
            border-bottom: 1px dashed #000;
            padding-bottom: 3px;
        }

        .product-item {
            margin-bottom: 4px;
            font-size: 13px;
        }

        .product-name {
            font-weight: bold;
            margin-bottom: 1px;
        }

        .product-details {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
        }

        .totals {
            margin-top: 8px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .total-final {
            font-weight: bold;
            font-size: 15px;
            border-top: 1px solid #000;
            padding-top: 3px;
            margin-top: 3px;
        }

        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 12px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }

        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #007bff;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">🖨️ Imprimir</button>

    <div class="ticket-container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">
                {{ $sale->company->name ?? 'EMPRESA' }}
            </div>
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

        <!-- Sale Info -->
        <div class="sale-info">
            <div class="sale-info-row">
                <span><strong>TICKET #{{ $sale->id }}</strong></span>
                <span>{{ $sale->created_at->format('d/m/Y') }}</span>
            </div>
            <div class="sale-info-row">
                <span>Hora:</span>
                <span>{{ $sale->created_at->format('H:i:s') }}</span>
            </div>
            <div class="sale-info-row">
                <span>Cliente:</span>
                <span>{{ $sale->client->firstname ?? 'CLIENTE GENERAL' }}</span>
            </div>
            @if($sale->typedocument)
            <div class="sale-info-row">
                <span>Tipo:</span>
                <span>{{ $sale->typedocument->description }}</span>
            </div>
            @endif
        </div>

        <!-- Products -->
        <div class="products">
            <div class="product-header">
                PRODUCTOS
            </div>

            @php
                $subtotal = 0;
                $totalIva = 0;
            @endphp

            @foreach($sale->details as $detail)
                @php
                    $subtotal += $detail->pricesale + $detail->nosujeta + $detail->exempt;
                    $totalIva += $detail->detained13;
                @endphp
                <div class="product-item">
                    <div class="product-name">
                        {{ $detail->product->name ?? 'Producto' }} {{ $detail->product->marca->name ?? '' }}
                    </div>
                    <div class="product-details">
                        <span>
                            {{ $detail->amountp ?? 1 }} x
                            ${{ number_format($detail->priceunit ?? 0, 2) }}
                        </span>
                        <span>
                            ${{ number_format($detail->pricesale ?? 0, 2) }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Totals -->
        <div class="totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>${{ number_format($subtotal, 2) }}</span>
            </div>
            <div class="total-row">
                <span>IVA:</span>
                <span>${{ number_format($totalIva, 2) }}</span>
            </div>
            <div class="total-row total-final">
                <span><strong>TOTAL:</strong></span>
                <span><strong>${{ number_format($subtotal + $totalIva, 2) }}</strong></span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            ¡GRACIAS POR SU COMPRA!<br>
            Conserve este ticket<br>
            <small>{{ now()->format('d/m/Y H:i:s') }}</small>
        </div>
    </div>

        <script>
        // Configuraciones para impresión silenciosa
        let printAttempted = false;

        // Intentar configurar impresión silenciosa en Chrome/Chromium
        function configurePrintSettings() {
            try {
                // Intentar usar la API experimental de Chrome para impresión silenciosa
                if (window.chrome && window.chrome.webstore) {
                    // Configuraciones específicas para Chrome
                    const printSettings = {
                        silent: true,
                        shouldPrintBackgrounds: true,
                        shouldPrintSelectionOnly: false,
                        marginsType: 1, // Sin márgenes
                        pageRanges: {}
                    };

                    // Intentar aplicar configuraciones si están disponibles
                    if (window.chrome.printing) {
                        window.chrome.printing.print(printSettings);
                        return true;
                    }
                }

                // Método para otros navegadores - usar CSS para optimizar
                return false;
            } catch (e) {
                console.log('No se pudo configurar impresión silenciosa:', e);
                return false;
            }
        }

        // Función de impresión optimizada
        function printDirect() {
            if (printAttempted) return;
            printAttempted = true;

            console.log('🖨️ Iniciando impresión directa...');

            // Intentar impresión silenciosa primero
            if (!configurePrintSettings()) {
                // Fallback: impresión normal
                window.print();
            }

            // Auto-cerrar después de un momento
            setTimeout(function() {
                console.log('⏰ Cerrando ventana automáticamente...');
                try {
                    window.close();
                } catch (e) {
                    console.log('No se pudo cerrar la ventana automáticamente');
                }
            }, 3000);
        }

        // Impresión automática inmediata
        window.addEventListener('load', function() {
            console.log('📄 Ticket cargado, preparando impresión...');

            // Impresión inmediata con múltiples intentos
            setTimeout(printDirect, 100);
        });

        // Capturar evento de después de imprimir
        window.addEventListener('afterprint', function() {
            setTimeout(function() {
                window.close();
            }, 1000);
        });

        // Configuración adicional para impresión
        if (window.chrome) {
            // Chrome específico
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'p') {
                    e.preventDefault();
                    window.print();
                }
            });
        }
    </script>
</body>
</html>
