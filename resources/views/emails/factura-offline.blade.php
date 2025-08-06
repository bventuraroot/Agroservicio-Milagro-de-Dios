<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura - {{ $nombreEmpresa ?? config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #2c5282 0%, #3182ce 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 30px;
        }

        .factura-info {
            background-color: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 25px;
            margin: 30px 0;
            text-align: center;
        }

        .factura-numero {
            font-size: 24px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .empresa-nombre {
            font-size: 18px;
            color: #4a5568;
            margin-bottom: 15px;
        }

        .fecha-emision {
            font-size: 14px;
            color: #718096;
        }

        .mensaje {
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 30px;
            color: #555;
        }

        .cliente-info {
            background-color: #edf2f7;
            border-left: 4px solid #3182ce;
            padding: 20px;
            margin: 25px 0;
            border-radius: 0 8px 8px 0;
        }

        .cliente-info h3 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .cliente-info p {
            margin: 5px 0;
            font-size: 14px;
            color: #4a5568;
        }

        .adjunto-info {
            background-color: #f0fff4;
            border: 1px solid #9ae6b4;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            text-align: center;
        }

        .adjunto-info .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .adjunto-info h3 {
            color: #22543d;
            margin-bottom: 10px;
        }

        .adjunto-info p {
            color: #38a169;
            font-size: 14px;
        }

        .nota-importante {
            background-color: #fffbeb;
            border: 1px solid #f6e05e;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }

        .nota-importante h4 {
            color: #744210;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .nota-importante p {
            color: #975a16;
            font-size: 14px;
            line-height: 1.6;
        }

        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .footer p {
            color: #888;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .contacto-info {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .contacto-info p {
            font-size: 12px;
            color: #718096;
        }

        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }

            .header, .content {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .factura-numero {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>📄 Factura Electrónica</h1>
            <p>Documento de Venta</p>
        </div>

        <div class="content">
            <div class="mensaje">
                <p>Estimado(a) Cliente,</p>
                <p>Adjunto encontrará su factura correspondiente a la compra realizada. Agradecemos su preferencia y confianza en nuestros servicios.</p>
            </div>

            <div class="factura-info">
                @if($numeroFactura)
                    <div class="factura-numero">{{ $numeroFactura }}</div>
                @endif

                @if($nombreEmpresa)
                    <div class="empresa-nombre">{{ $nombreEmpresa }}</div>
                @endif

                <div class="fecha-emision">
                    📅 Fecha de emisión: {{ now()->format('d/m/Y H:i') }}
                </div>
            </div>

            @if(isset($data['cliente']) || isset($data['client']))
            <div class="cliente-info">
                <h3>👤 Información del Cliente</h3>
                @php
                    $cliente = $data['cliente'] ?? $data['client'] ?? null;
                @endphp

                @if($cliente)
                    @if(isset($cliente['nombre']) || isset($cliente['firstname']))
                        <p><strong>Nombre:</strong>
                            {{ $cliente['nombre'] ?? ($cliente['firstname'] . ' ' . ($cliente['lastname'] ?? '')) }}
                        </p>
                    @endif

                    @if(isset($cliente['email']))
                        <p><strong>Email:</strong> {{ $cliente['email'] }}</p>
                    @endif

                    @if(isset($cliente['telefono']) || isset($cliente['tel1']))
                        <p><strong>Teléfono:</strong> {{ $cliente['telefono'] ?? $cliente['tel1'] }}</p>
                    @endif

                    @if(isset($cliente['direccion']) || isset($cliente['address']))
                        <p><strong>Dirección:</strong> {{ $cliente['direccion'] ?? $cliente['address'] }}</p>
                    @endif
                @endif
            </div>
            @endif

            <div class="adjunto-info">
                <div class="icon">📎</div>
                <h3>Documento Adjunto</h3>
                <p>Su factura se encuentra adjunta a este correo en formato PDF.</p>
                <p>Por favor, conserve este documento para sus registros contables.</p>
            </div>

            <div class="nota-importante">
                <h4>⚠️ Información Importante</h4>
                <p>Este documento es su comprobante de compra oficial. Si tiene alguna pregunta sobre su factura o necesita asistencia adicional, no dude en contactarnos.</p>
                <p><strong>Nota:</strong> Este es un proceso automático, por favor no responda directamente a este correo.</p>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <p style="font-size: 16px; color: #4a5568;">
                    <strong>¡Gracias por su compra!</strong>
                </p>
                <p style="font-size: 14px; color: #718096;">
                    Esperamos seguir siendo su proveedor de confianza
                </p>
            </div>
        </div>

        <div class="footer">
            <p><strong>{{ $nombreEmpresa ?? config('app.name') }}</strong></p>
            <p>Este correo fue enviado automáticamente</p>

            <div class="contacto-info">
                <p>Para consultas o soporte, contáctenos a través de nuestros canales oficiales</p>
                <p>© {{ date('Y') }} {{ $nombreEmpresa ?? config('app.name') }}. Todos los derechos reservados.</p>
            </div>
        </div>
    </div>
</body>
</html>
