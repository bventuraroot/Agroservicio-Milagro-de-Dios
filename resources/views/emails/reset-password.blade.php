<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Restablecer Contraseña - Agroservicio Milagro de Dios</title>
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
            color: #28a745;
            font-size: 28px;
            font-weight: bold;
            text-decoration: none;
        }

        .security-info {
            background-color: #e7f3ff;
            padding: 20px;
            border-left: 4px solid #007bff;
            margin: 20px 0;
            border-radius: 5px;
        }

        .security-info h4 {
            margin: 0 0 10px 0;
            color: #007bff;
            font-size: 16px;
        }

        .security-info p {
            margin: 5px 0;
            font-size: 14px;
            color: #333;
        }

        .reset-button {
            display: inline-block;
            padding: 15px 30px;
            background-color: #28a745;
            color: white !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            font-size: 16px;
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }

        .reset-button:hover {
            background-color: #218838;
        }

        .warning-note {
            background-color: #fff3cd;
            padding: 15px;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            font-size: 14px;
            color: #856404;
            margin: 20px 0;
        }

        .footer-note {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-size: 12px;
            color: #6c757d;
            margin: 20px 0;
            text-align: center;
        }

        .agro-icon {
            font-size: 24px;
            color: #28a745;
        }

        .contact-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>

<body style="margin:0;padding:0;word-spacing:normal;background-color:#f4f4f4;">
    <div role="article" aria-roledescription="email" lang="es"
        style="text-size-adjust:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;background-color:#f4f4f4;">
        <table role="presentation" style="width:100%;border:none;border-spacing:0;">
            <tr>
                <td align="center" style="padding:20px 0;">
                    <table role="presentation"
                        style="width:94%;max-width:600px;border:none;border-spacing:0;text-align:left;font-family:Arial,sans-serif;font-size:16px;line-height:24px;color:#333333;background-color:#ffffff;border-radius:10px;box-shadow:0 4px 6px rgba(0, 0, 0, 0.1);">

                        <!-- Header -->
                        <tr>
                            <td style="padding:30px 30px 20px 30px;text-align:center;background: linear-gradient(135deg, #28a745 0%, #20c997 100%);border-radius:10px 10px 0 0;">
                                <h1 class="header-logo" style="margin:0;color:#ffffff;">
                                    🌾 Agroservicio Milagro de Dios
                                </h1>
                                <p style="margin:5px 0 0 0;color:#e8f5e8;font-size:14px;">
                                    Tu aliado en el campo
                                </p>
                            </td>
                        </tr>

                        <!-- Contenido Principal -->
                        <tr>
                            <td style="padding:30px;background-color:#ffffff;">
                                <h2 style="margin-top:0;margin-bottom:20px;font-size:24px;line-height:32px;font-weight:bold;color:#28a745;">
                                    🔐 Restablecimiento de Contraseña
                                </h2>

                                <p style="margin:0 0 20px 0;font-size:16px;line-height:24px;">
                                    Hola,
                                </p>

                                <p style="margin:0 0 20px 0;font-size:16px;line-height:24px;">
                                    Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en <strong>Agroservicio Milagro de Dios</strong>.
                                </p>

                                <!-- Información de Seguridad -->
                                <div class="security-info">
                                    <h4>🛡️ Información de Seguridad</h4>
                                    <p>• Tu seguridad es nuestra prioridad</p>
                                    <p>• Este enlace es válido por <strong>{{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutos</strong></p>
                                    <p>• Solo funciona una vez</p>
                                    <p>• Se genera un token único para tu protección</p>
                                </div>

                                <p style="margin:20px 0;font-size:16px;line-height:24px;text-align:center;">
                                    Para continuar con el restablecimiento, haz clic en el siguiente botón:
                                </p>

                                <!-- Botón de Reset -->
                                <div style="text-align:center;margin:30px 0;">
                                    <a href="{{ $resetUrl }}" class="reset-button">
                                        🔑 Restablecer Mi Contraseña
                                    </a>
                                </div>

                                <!-- Nota de Advertencia -->
                                <div class="warning-note">
                                    <p style="margin:0;"><strong>⚠️ Importante:</strong></p>
                                    <p style="margin:5px 0 0 0;">Si no solicitaste este restablecimiento, puedes ignorar este correo. Tu cuenta permanece segura y no se realizarán cambios.</p>
                                </div>

                                <p style="margin:20px 0;font-size:14px;line-height:20px;color:#666;">
                                    Si tienes problemas para hacer clic en el botón, copia y pega la siguiente URL en tu navegador:
                                </p>
                                <p style="margin:0 0 20px 0;font-size:12px;word-break:break-all;color:#007bff;">
                                    <a href="{{ $resetUrl }}" style="color:#007bff;">{{ $resetUrl }}</a>
                                </p>

                                <!-- Información de Contacto -->
                                <div class="contact-info">
                                    <h4 style="margin:0 0 10px 0;color:#28a745;">📞 ¿Necesitas Ayuda?</h4>
                                    <p style="margin:0;font-size:14px;">
                                        Si tienes alguna pregunta o necesitas asistencia adicional, no dudes en contactarnos.
                                    </p>
                                </div>

                                <p style="margin:30px 0 0 0;font-size:16px;line-height:24px;">
                                    Atentamente,<br>
                                    <strong>El equipo de Agroservicio Milagro de Dios</strong> 🌱
                                </p>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td class="footer-note" style="border-radius:0 0 10px 10px;">
                                <p style="margin:0 0 5px 0;">
                                    © {{ date('Y') }} Agroservicio Milagro de Dios - Todos los derechos reservados
                                </p>
                                <p style="margin:0;font-size:11px;">
                                    Este es un correo automático, por favor no responder a esta dirección.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
