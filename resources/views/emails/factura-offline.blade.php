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

        .mensaje {
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 30px;
            color: #555;
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
            font-size: 32px;
            margin-bottom: 10px;
        }

        .adjunto-info h3 {
            color: #22543d;
            margin-bottom: 10px;
        }

        .adjunto-info p {
            color: #2f855a;
            margin: 5px 0;
        }

        .nota-importante {
            background-color: #fffaf0;
            border: 1px solid #f6ad55;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }

        .nota-importante h4 {
            color: #c05621;
            margin-bottom: 10px;
        }

        .nota-importante p {
            color: #744210;
            margin: 5px 0;
        }

        .footer {
            background-color: #2d3748;
            color: white;
            padding: 30px;
            text-align: center;
        }

        .footer p {
            margin: 5px 0;
        }

        .contacto-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #4a5568;
        }

        .contacto-info p {
            font-size: 12px;
            opacity: 0.8;
        }

        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 5px;
            }

            .header {
                padding: 30px 20px;
            }

            .content {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 24px;
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
                    📅 Fecha de emisión: {{ $data['fecha_emision'] ?? now()->format('d/m/Y H:i') }}
                </div>
            </div>

            @if(isset($data['cliente']) && $data['cliente']['nombre'])
                <div class="cliente-info">
                    <h3>👤 Información del Cliente</h3>
                    <p><strong>Nombre:</strong> {{ $data['cliente']['nombre'] }}</p>
                    @if($data['cliente']['email'])
                        <p><strong>Email:</strong> {{ $data['cliente']['email'] }}</p>
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
