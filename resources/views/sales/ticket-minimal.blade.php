<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #{{ $sale->id }}</title>
    <style>
        @media print {
            @page { width: 80mm; margin: 0; }
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
        }

        body {
            font-family: 'Courier New', monospace;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
            font-size: 16px;
            line-height: 1.4;
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

        .sale-info {
            margin-bottom: 10px;
            font-size: 14px;
        }

        .sale-row {
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

        .product-item {
            margin-bottom: 5px;
            font-size: 10px;
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
            font-size: 11px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .total-final {
            font-weight: bold;
            font-size: 12px;
            border-top: 1px solid #000;
            padding-top: 3px;
            margin-top: 5px;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 10px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }

        .no-print {
            margin: 20px 0;
            text-align: center;
        }

        button {
            padding: 10px 20px;
            margin: 5px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        button:hover {
            background-color: #0056b3;
        }

        .btn-success {
            background-color: #28a745;
        }

        .btn-warning {
            background-color: #ffc107;
            color: black;
        }
    </style>
</head>
<body>
    <!-- Controles (no se imprimen) -->
    <div class="no-print">
        <h3>🎫 Ticket de Venta #{{ $sale->id }}</h3>

        <div style="margin: 15px 0; padding: 10px; background-color: #f8f9fa; border-radius: 5px;">
            <strong>🖨️ Impresora:</strong> Se usará la impresora predeterminada del sistema<br>
            <small>Configure su impresora de 80mm como predeterminada para mejores resultados</small>
        </div>

        <button onclick="window.print()">🖨️ Imprimir Ticket</button>
        <button onclick="window.close()" class="btn-warning">❌ Cerrar</button>
        <button onclick="testPrint()" class="btn-success">🧪 Prueba</button>

        @if(!$autoprint)
            <div style="margin-top: 10px;">
                <span style="background-color: #ffc107; color: black; padding: 5px 10px; border-radius: 3px;">
                    👁️ Modo Vista - No se imprimirá automáticamente
                </span>
            </div>
        @else
            <div style="margin-top: 10px;">
                <span style="background-color: #28a745; color: white; padding: 5px 10px; border-radius: 3px;">
                    🖨️ Auto-Impresión Activada
                </span>
            </div>
        @endif
    </div>

    <!-- Contenido del ticket -->
    <div class="ticket-container">
        <!-- Encabezado -->
        <div class="header">
            <div class="company-name">{{ $sale->company->name ?? 'AGROSERVICIO MILAGRO DE DIOS' }}</div>
            <div style="font-size: 10px;">
                @if($sale->company->address ?? false)
                    {{ $sale->company->address }}<br>
                @endif
                @if($sale->company->phone ?? false)
                    Tel: {{ $sale->company->phone }}<br>
                @endif
                @if($sale->company->email ?? false)
                    {{ $sale->company->email }}
                @endif
            </div>
        </div>

        <!-- Información de la venta -->
        <div class="sale-info">
            <div class="sale-row">
                <span><strong>Ticket #:</strong></span>
                <span>{{ $sale->id }}</span>
            </div>
            <div class="sale-row">
                <span><strong>Fecha:</strong></span>
                <span>{{ \Carbon\Carbon::parse($sale->date)->format('d/m/Y H:i') }}</span>
            </div>
            <div class="sale-row">
                <span><strong>Tipo:</strong></span>
                <span>{{ $sale->typedocument->description ?? 'Venta' }}</span>
            </div>
            <div class="sale-row">
                <span><strong>Cliente:</strong></span>
                <span>
                    @if($sale->client)
                        @if($sale->client->tpersona == 'N')
                            {{ $sale->client->firstname }} {{ $sale->client->firstlastname }}
                        @else
                            {{ substr($sale->client->name_contribuyente ?? 'Cliente', 0, 20) }}
                        @endif
                    @else
                        Venta al menudeo
                    @endif
                </span>
            </div>
            <div class="sale-row">
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
            <div style="text-align: center; font-weight: bold; margin-bottom: 5px;">
                PRODUCTOS
            </div>

            @foreach($sale->details as $detail)
                <div class="product-item">
                    <div class="product-name">
                        {{ $detail->product->name ?? 'Producto' }} {{ $detail->product->marca->name ?? '' }}
                    </div>
                    <div class="product-details">
                        <span>{{ $detail->amountp ?? 1 }} x ${{ number_format($detail->priceunit ?? 0, 2) }}</span>
                        <span>${{ number_format(($detail->pricesale ?? 0) + ($detail->nosujeta ?? 0) + ($detail->exempt ?? 0), 2) }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Totales -->
        <div class="totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>${{ number_format($subtotal, 2) }}</span>
            </div>

            @if($totalIva > 0)
                <div class="total-row">
                    <span>IVA (13%):</span>
                    <span>${{ number_format($totalIva, 2) }}</span>
                </div>
            @endif

            <div class="total-row total-final">
                <span>TOTAL:</span>
                <span>${{ number_format($total, 2) }}</span>
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

    <script>
        console.log('🎫 Ticket cargado para venta #{{ $sale->id }}');

        // Auto-impresión si está habilitada
        const autoprint = {{ $autoprint ? 'true' : 'false' }};
        let hasAutoprinted = false;

        if (autoprint) {
            window.addEventListener('load', function() {
                setTimeout(function() {
                    if (!hasAutoprinted) {
                        hasAutoprinted = true;
                        console.log('🖨️ Auto-imprimiendo ticket...');
                        window.print();
                    }
                }, 1000);
            });
        } else {
            console.log('👁️ Modo vista - Auto-impresión deshabilitada');
        }

        // Función de prueba
        function testPrint() {
            const testContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Prueba 80mm</title>
                    <style>
                        @page { width: 80mm; margin: 0; }
                        body { font-family: 'Courier New', monospace; font-size: 12px; margin: 5mm; text-align: center; }
                    </style>
                </head>
                <body>
                    <h3>PRUEBA DE IMPRESIÓN</h3>
                    <p>══════════════════════</p>
                    <p>Ancho: 80mm</p>
                    <p>Ticket: #{{ $sale->id }}</p>
                    <p>Fecha: ${new Date().toLocaleString()}</p>
                    <p>══════════════════════</p>
                    <p>✅ Si ve este texto bien,<br>su impresora está OK</p>
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

        // Cerrar después de imprimir (opcional)
        window.addEventListener('afterprint', function() {
            // Uncomment para cerrar automáticamente después de imprimir
            // setTimeout(function() { window.close(); }, 2000);
        });
    </script>
</body>
</html>
