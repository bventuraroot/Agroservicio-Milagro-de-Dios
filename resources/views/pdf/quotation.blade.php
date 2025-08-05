<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Cotización {{ $quotation->quote_number }}</title>

    <style type="text/css">
        * {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            font-size: 12px;
            line-height: 1.4;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .logo-section {
            width: 25%;
            vertical-align: top;
            padding: 10px;
            text-align: center;
        }

        .logo-img {
            max-width: 150px;
            max-height: 150px;
            height: auto;
        }

        .company-info {
            width: 45%;
            vertical-align: top;
            padding: 10px;
        }

        .quote-info {
            width: 30%;
            vertical-align: top;
            padding: 10px;
            border: 2px solid #1EBB51;
            background-color: #f8f9fa;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1EBB51;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 11px;
            color: #666;
        }

        .quote-title {
            font-size: 18px;
            font-weight: bold;
            color: #1EBB51;
            text-align: center;
            margin-bottom: 10px;
        }

        .quote-number {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
        }

        .quote-details {
            font-size: 10px;
        }

        .client-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }

        .client-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .client-info {
            display: block;
            width: 100%;
        }

        .client-info div {
            margin-bottom: 3px;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10px;
        }

        .products-table th {
            background-color: #1EBB51;
            color: white;
            padding: 8px 5px;
            text-align: center;
            border: 1px solid #ddd;
            font-weight: bold;
        }

        .products-table td {
            padding: 6px 5px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .products-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .products-table .text-left {
            text-align: left;
        }

        .products-table .text-right {
            text-align: right;
        }

        .totals-section {
            width: 100%;
            margin-top: 20px;
        }

        .totals-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
            font-size: 11px;
        }

        .totals-table td {
            padding: 5px 10px;
            border: 1px solid #ddd;
        }

        .totals-table .label {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: right;
        }

        .totals-table .amount {
            text-align: right;
            background-color: white;
        }

        .totals-table .total-final {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }

        .terms-section {
            margin-top: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }

        .terms-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .terms-content {
            font-size: 10px;
            line-height: 1.5;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }

        .status-approved {
            background-color: #28a745;
            color: white;
        }

        .status-rejected {
            background-color: #dc3545;
            color: white;
        }

        .status-expired {
            background-color: #6c757d;
            color: white;
        }

        @page {
            margin: 2cm 1.5cm;
        }
    </style>
</head>

<body>
    <!-- Header con información de la empresa y cotización -->
    <table class="header-table">
        <tr>
            <td class="logo-section">
                                                                @php
                    $logoPath = public_path('assets/img/logo-pdf-large.png');
                    $showLogo = false;
                    $logoBase64 = '';

                    if (file_exists($logoPath)) {
                        try {
                            $logoData = @file_get_contents($logoPath);
                            if ($logoData !== false && strlen($logoData) > 0) {
                                if (strlen($logoData) < 2000000) {
                                    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
                                    $showLogo = true;
                                }
                            }
                        } catch (\Exception $e) {
                            $showLogo = false;
                        }
                    }
                @endphp

                @if($showLogo)
                    <div style="text-align: center; padding: 5px;">
                        <img src="{{ $logoBase64 }}"
                             alt="Logo"
                             style="width: 120px; height: 120px;">
                    </div>
                @else
                    <div style="border: 2px dashed #ccc; padding: 15px; text-align: center; font-size: 10px; color: #999;">
                        <strong>{{ $quotation->company->name ?? 'EMPRESA' }}</strong><br>
                        <small>LOGO</small>
                    </div>
                @endif
            </td>
            <td class="company-info">
                <div class="company-name">{{ $quotation->company->name ?? 'Empresa' }}</div>
                <div class="company-details">
                    @if(isset($quotation->company->address) && $quotation->company->address)
                        <div>{{ $quotation->company->address->address ?? 'Dirección no disponible' }}</div>
                        <div>{{ $quotation->company->address->municipality ?? '' }}{{ $quotation->company->address->department ? ', ' . $quotation->company->address->department : '' }}</div>
                    @endif
                    @if($quotation->company->phone ?? false)
                        <div>Tel: {{ $quotation->company->phone }}</div>
                    @endif
                    @if($quotation->company->email ?? false)
                        <div>Email: {{ $quotation->company->email }}</div>
                    @endif
                    @if($quotation->company->nit ?? false)
                        <div>NIT: {{ $quotation->company->nit }}</div>
                    @endif
                </div>
            </td>
            <td class="quote-info">
                <div class="quote-title">COTIZACIÓN</div>
                <div class="quote-number">{{ $quotation->quote_number }}</div>
                <div class="quote-details">
                    <div><strong>Fecha:</strong> {{ $quotation->quote_date ? $quotation->quote_date->format('d/m/Y') : 'N/A' }}</div>
                    <div><strong>Válida hasta:</strong> {{ $quotation->valid_until ? $quotation->valid_until->format('d/m/Y') : 'N/A' }}</div>
                    <div><strong>Estado:</strong>
                        <span style="margin-top: 10px; align-items: center; align-content: center;" class="status-badge status-{{ $quotation->status }}">
                            {{ $quotation->getStatusInSpanish() }}
                        </span>
                    </div>
                    @if($quotation->payment_terms)
                        <div><strong>Términos de pago:</strong> {{ $quotation->payment_terms }}</div>
                    @endif
                    @if($quotation->delivery_time)
                        <div><strong>Tiempo de entrega:</strong> {{ $quotation->delivery_time }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Información del cliente -->
    <div class="client-section">
        <div class="client-title">INFORMACIÓN DEL CLIENTE</div>
        <div class="client-info">
            <div><strong>Cliente:</strong> {{ $quotation->client->razonsocial }}</div>
            @if($quotation->client->nit)
                <div><strong>NIT:</strong> {{ $quotation->client->nit }}</div>
            @endif
            @if($quotation->client->ncr)
                <div><strong>NCR:</strong> {{ $quotation->client->ncr }}</div>
            @endif
            @if($quotation->client->email)
                <div><strong>Email:</strong> {{ $quotation->client->email }}</div>
            @endif
            @if($quotation->client->address)
                <div><strong>Dirección:</strong> {{ $quotation->client->address->address ?? 'No disponible' }}</div>
            @endif
        </div>
    </div>

    <!-- Tabla de productos -->
    <table class="products-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">Producto/Servicio</th>
                <th style="width: 10%;">Cantidad</th>
                <th style="width: 12%;">Precio Unit.</th>
                <th style="width: 10%;">Descuento</th>
                <th style="width: 12%;">Subtotal</th>
                <th style="width: 8%;">IVA</th>
                <th style="width: 8%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->details as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">
                        <strong>{{ $detail->product->name }}</strong>
                        @if($detail->description)
                            <br><small>{{ $detail->description }}</small>
                        @endif
                    </td>
                    <td>{{ number_format($detail->quantity, 0) }}</td>
                    <td class="text-right">${{ number_format($detail->unit_price, 2) }}</td>
                    <td class="text-right">
                        @if($detail->discount_percentage > 0)
                            {{ $detail->discount_percentage }}%<br>
                            <small>${{ number_format($detail->discount_amount, 2) }}</small>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">${{ number_format($detail->subtotal, 2) }}</td>
                    <td class="text-right">${{ number_format($detail->tax_amount, 2) }}</td>
                    <td class="text-right">${{ number_format($detail->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totales -->
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="amount">${{ number_format($quotation->subtotal, 2) }}</td>
            </tr>
            @if($quotation->discount_amount > 0)
                <tr>
                    <td class="label">Descuento General:</td>
                    <td class="amount">-${{ number_format($quotation->discount_amount, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td class="label">IVA (13%):</td>
                <td class="amount">${{ number_format($quotation->taxes, 2) }}</td>
            </tr>
            <tr class="total-final">
                <td class="label">TOTAL:</td>
                <td class="amount">${{ number_format($quotation->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- Notas y términos -->
    @if($quotation->notes || $quotation->terms_conditions)
        <div class="terms-section">
            @if($quotation->notes)
                <div class="terms-title">NOTAS ADICIONALES</div>
                <div class="terms-content">{{ $quotation->notes }}</div>
            @endif

            @if($quotation->terms_conditions)
                <div class="terms-title" style="margin-top: 15px;">TÉRMINOS Y CONDICIONES</div>
                <div class="terms-content">{{ $quotation->terms_conditions }}</div>
            @endif
        </div>
    @else
        <div class="terms-section">
            <div class="terms-title">TÉRMINOS Y CONDICIONES GENERALES</div>
            <div class="terms-content">
                <p>• Esta cotización es válida hasta la fecha indicada.</p>
                <p>• Los precios están sujetos a cambio sin previo aviso.</p>
                <p>• Una vez aceptada la cotización, se procederá según los términos acordados.</p>
                <p>• Para cualquier consulta, favor contactarnos a través de los medios indicados.</p>
            </div>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Cotización generada el {{ now()->format('d/m/Y H:i:s') }} por {{ $quotation->user->name }}</p>
        <p>Esta cotización fue generada electrónicamente y no requiere firma física.</p>
    </div>
</body>

</html>
