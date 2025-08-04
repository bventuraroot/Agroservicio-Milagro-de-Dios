<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Venta #{{ $sale->id }}</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.2;
            margin: 0;
            padding: 10px;
            background: white;
        }
        .receipt {
            max-width: 300px;
            margin: 0 auto;
            border: 1px solid #ccc;
            padding: 10px;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 10px;
            color: #666;
        }
        .sale-info {
            margin-bottom: 10px;
        }
        .sale-info div {
            margin-bottom: 2px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .items-table th {
            text-align: left;
            border-bottom: 1px solid #ccc;
            padding: 2px 0;
            font-size: 10px;
        }
        .items-table td {
            padding: 2px 0;
            font-size: 10px;
        }
        .item-name {
            width: 60%;
        }
        .item-qty {
            width: 15%;
            text-align: center;
        }
        .item-price {
            width: 25%;
            text-align: right;
        }
        .totals {
            border-top: 1px dashed #ccc;
            padding-top: 10px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .total-row.final {
            font-weight: bold;
            font-size: 14px;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 10px;
            color: #666;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .receipt {
                border: none;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div class="company-name">{{ $sale->company->name ?? 'Mi Librería' }}</div>
            <div class="company-info">
                {{ $sale->company->address ?? 'Dirección de la empresa' }}<br>
                Tel: {{ $sale->company->phone ?? 'N/A' }}<br>
                NIT: {{ $sale->company->nit ?? 'N/A' }}
            </div>
        </div>

        <div class="sale-info">
            <div><strong>RECIBO #{{ $sale->id }}</strong></div>
            <div>Fecha: {{ \Carbon\Carbon::parse($sale->date)->format('d/m/Y') }}</div>
            <div>Hora: {{ \Carbon\Carbon::parse($sale->created_at)->format('H:i:s') }}</div>
            @if($sale->client)
                <div>Cliente: {{ $sale->client->name ??
                    trim($sale->client->firstname . ' ' . $sale->client->secondname . ' ' .
                         $sale->client->firstlastname . ' ' . $sale->client->secondlastname) }}</div>
            @else
                <div>Cliente: Venta al menudeo</div>
            @endif
            <div>A cuenta de: {{ $sale->acuenta ?? 'N/A' }}</div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th class="item-name">PRODUCTO</th>
                    <th class="item-qty">CANT</th>
                    <th class="item-price">PRECIO</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->details as $detail)
                <tr>
                    <td class="item-name">{{ $detail->product->name ?? 'Producto' }}</td>
                    <td class="item-qty">{{ $detail->amountp }}</td>
                    <td class="item-price">${{ number_format($detail->priceunit, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            @php
                $subtotal = $sale->details->sum('pricesale');
                $nosujeta = $sale->details->sum('nosujeta');
                $exempt = $sale->details->sum('exempt');
                $iva = $sale->details->sum('detained13');
                $total = $sale->totalamount;
            @endphp

            @if($subtotal > 0)
            <div class="total-row">
                <span>Subtotal:</span>
                <span>${{ number_format($subtotal, 2) }}</span>
            </div>
            @endif

            @if($iva > 0)
            <div class="total-row">
                <span>IVA (13%):</span>
                <span>${{ number_format($iva, 2) }}</span>
            </div>
            @endif

            @if($nosujeta > 0)
            <div class="total-row">
                <span>No Sujetas:</span>
                <span>${{ number_format($nosujeta, 2) }}</span>
            </div>
            @endif

            @if($exempt > 0)
            <div class="total-row">
                <span>Exentas:</span>
                <span>${{ number_format($exempt, 2) }}</span>
            </div>
            @endif

            <div class="total-row final">
                <span>TOTAL:</span>
                <span>${{ number_format($total, 2) }}</span>
            </div>
        </div>

        <div class="footer">
            <div>¡Gracias por su compra!</div>
            <div>Vendedor: {{ $sale->user->name ?? 'N/A' }}</div>
            <div>Forma de pago:
                @switch($sale->waytopay)
                    @case('1')
                        Contado
                        @break
                    @case('2')
                        A crédito
                        @break
                    @case('3')
                        Tarjeta
                        @break
                    @default
                        N/A
                @endswitch
            </div>
            <div style="margin-top: 10px;">
                {{ date('d/m/Y H:i:s') }}
            </div>
        </div>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
