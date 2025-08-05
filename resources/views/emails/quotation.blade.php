<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Cotización {{ $data['quote_number'] ?? '' }}</title>
    <!--[if mso]>
  <style>
    table {border-collapse:collapse;border-spacing:0;border:none;margin:0;}
    div, td {padding:0;}
    div {margin:0 !important;}
	</style>
  <noscript>
    <xml>
      <o:OfficeDocumentSettings>
        <o:PixelsPerInch>96</o:PixelsPerInch>
      </o:OfficeDocumentSettings>
    </xml>
  </noscript>
  <![endif]-->
    <style>
        table,
        td,
        div,
        h1,
        h2,
        h3,
        p {
            font-family: Arial, sans-serif;
        }

        .header-logo {
            color: #007bff;
            font-size: 28px;
            font-weight: bold;
            text-decoration: none;
        }

        .quote-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin: 20px 0;
        }

        .quote-info h4 {
            margin: 0 0 10px 0;
            color: #007bff;
            font-size: 16px;
        }

        .quote-info p {
            margin: 5px 0;
            font-size: 14px;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
        }

        .button:hover {
            background-color: #0056b3;
        }

        .footer-note {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-size: 12px;
            color: #6c757d;
            margin: 20px 0;
        }

        @media screen and (max-width: 530px) {
            .col-lge {
                max-width: 100% !important;
            }
        }

        @media screen and (min-width: 531px) {
            .col-sml {
                max-width: 27% !important;
            }

            .col-lge {
                max-width: 73% !important;
            }
        }
    </style>
</head>

<body style="margin:0;padding:0;word-spacing:normal;background-color:#f4f4f4;">
    <div role="article" aria-roledescription="email" lang="es"
        style="text-size-adjust:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;background-color:#f4f4f4;">
        <table role="presentation" style="width:100%;border:none;border-spacing:0;">
            <tr>
                <td align="center" style="padding:0;">
                    <!--[if mso]>
          <table role="presentation" align="center" style="width:600px;">
          <tr>
          <td>
          <![endif]-->
                    <table role="presentation"
                        style="width:94%;max-width:600px;border:none;border-spacing:0;text-align:left;font-family:Arial,sans-serif;font-size:16px;line-height:22px;color:#363636;">

                        <!-- Header -->
                        <tr>
                            <td style="padding:30px 30px 20px 30px;text-align:center;background-color:#ffffff;border-bottom:3px solid #007bff;">
                                <a href="#" class="header-logo">{{ $data['company_name'] ?? 'Nuestra Empresa' }}</a>
                                <p style="margin:10px 0 0 0;color:#6c757d;font-size:14px;">Cotización Empresarial</p>
                            </td>
                        </tr>

                        <!-- Contenido Principal -->
                        <tr>
                            <td style="padding:30px;background-color:#ffffff;">
                                <h2 style="margin-top:0;margin-bottom:20px;font-size:24px;line-height:32px;font-weight:bold;color:#007bff;">
                                    Estimado {{ $data['nombre'] }}
                                </h2>

                                <p style="margin:0 0 20px 0;font-size:16px;line-height:24px;">
                                    Esperamos que se encuentre bien. Nos complace enviarle la cotización solicitada con el detalle de productos y servicios que hemos preparado especialmente para usted.
                                </p>

                                <!-- Información de la Cotización -->
                                <div class="quote-info">
                                    <h4>📋 Detalles de la Cotización</h4>
                                    @if(isset($data['quote_number']))
                                        <p><strong>Número de Cotización:</strong> {{ $data['quote_number'] }}</p>
                                    @endif
                                    @if(isset($data['quotation']))
                                        <p><strong>Fecha de Cotización:</strong> {{ $data['quotation']->quote_date->format('d/m/Y') }}</p>
                                        <p><strong>Válida hasta:</strong> {{ $data['quotation']->valid_until->format('d/m/Y') }}</p>
                                        <p><strong>Total Cotizado:</strong> ${{ number_format($data['quotation']->total_amount, 2) }} {{ $data['quotation']->currency }}</p>
                                        @if($data['quotation']->payment_terms)
                                            <p><strong>Términos de Pago:</strong> {{ $data['quotation']->payment_terms }}</p>
                                        @endif
                                        @if($data['quotation']->delivery_time)
                                            <p><strong>Tiempo de Entrega:</strong> {{ $data['quotation']->delivery_time }}</p>
                                        @endif
                                    @endif
                                </div>

                                @if(isset($data['custom_message']) && $data['custom_message'])
                                    <div style="background-color:#e3f2fd;padding:15px;border-radius:5px;margin:20px 0;">
                                        <h4 style="margin:0 0 10px 0;color:#1976d2;">💬 Mensaje Personal</h4>
                                        <p style="margin:0;font-style:italic;">{{ $data['custom_message'] }}</p>
                                    </div>
                                @endif

                                <p style="margin:20px 0;font-size:16px;line-height:24px;">
                                    Adjunto a este correo encontrará el documento PDF con todos los detalles de la cotización, incluyendo:
                                </p>

                                <ul style="margin:0 0 20px 20px;font-size:14px;line-height:20px;">
                                    <li>Descripción detallada de productos/servicios</li>
                                    <li>Precios unitarios y totales</li>
                                    <li>Términos y condiciones aplicables</li>
                                    <li>Información de contacto para consultas</li>
                                </ul>

                                <p style="margin:20px 0;font-size:16px;line-height:24px;">
                                    Si tiene alguna pregunta sobre esta cotización o desea realizar alguna modificación, no dude en contactarnos. Estaremos encantados de asistirle.
                                </p>

                                <!-- Información de Contacto -->
                                <div style="background-color:#f8f9fa;padding:15px;border-radius:5px;margin:20px 0;">
                                    <h4 style="margin:0 0 10px 0;color:#495057;">📞 ¿Necesita Ayuda?</h4>
                                    <p style="margin:0;font-size:14px;">
                                        Para cualquier consulta sobre esta cotización, puede contactarnos a través de nuestros canales habituales de comunicación.
                                    </p>
                                </div>

                                <p style="margin:20px 0 0 0;font-size:16px;line-height:24px;">
                                    Agradecemos su interés en nuestros productos y servicios, y esperamos poder trabajar con usted pronto.
                                </p>

                                <p style="margin:20px 0;font-size:16px;line-height:24px;">
                                    <strong>Saludos cordiales,</strong><br>
                                    El equipo de {{ $data['company_name'] ?? 'Nuestra Empresa' }}
                                </p>
                            </td>
                        </tr>

                        <!-- Nota Importante -->
                        <tr>
                            <td style="padding:0 30px 30px 30px;background-color:#ffffff;">
                                <div class="footer-note">
                                    <p style="margin:0 0 10px 0;font-weight:bold;">📌 Importante:</p>
                                    <ul style="margin:0;padding-left:20px;">
                                        <li>Esta cotización tiene validez limitada según la fecha indicada</li>
                                        <li>Los precios pueden estar sujetos a cambios sin previo aviso</li>
                                        <li>Para proceder con el pedido, favor confirmar por escrito</li>
                                        <li>Este correo fue generado automáticamente</li>
                                    </ul>
                                </div>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td style="padding:30px;text-align:center;font-size:12px;background-color:#343a40;color:#ffffff;">
                                <p style="margin:0 0 15px 0;font-size:14px;font-weight:bold;">
                                    {{ $data['company_name'] ?? 'Nuestra Empresa' }}
                                </p>
                                <p style="margin:0 0 10px 0;">
                                    Sistema de Cotizaciones Empresariales
                                </p>
                                <p style="margin:0;color:#adb5bd;">
                                    © {{ date('Y') }} - Todos los derechos reservados
                                </p>
                            </td>
                        </tr>
                    </table>
                    <!--[if mso]>
          </td>
          </tr>
          </table>
          <![endif]-->
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
