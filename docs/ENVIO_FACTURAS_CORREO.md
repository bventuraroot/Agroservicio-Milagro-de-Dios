# Envío de Facturas por Correo Electrónico

## Descripción

Esta funcionalidad permite enviar facturas por correo electrónico usando **exactamente la configuración que ya tienes en tu archivo `.env`**. La implementación incluye validación de datos, manejo de errores, y una interfaz moderna y intuitiva.

## Características

- ✅ **Usa tu configuración del `.env`** (MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD, etc.)
- ✅ **No modifica ninguna configuración** existente
- ✅ **Validación robusta** de datos de entrada
- ✅ **Manejo de errores** con logging
- ✅ **Interfaz moderna** con SweetAlert2
- ✅ **Plantilla responsive** y profesional
- ✅ **Generación automática** de PDF
- ✅ **Adjunto automático** del PDF al correo
- ✅ **Múltiples formas de uso** (botones, JavaScript directo)

## Archivos Implementados

### Backend
- `app/Mail/EnviarFacturaOffline.php` - Clase de correo para facturas
- `app/Http/Controllers/SaleController.php` - Método `enviarFacturaPorCorreo()`
- `resources/views/emails/factura-offline.blade.php` - Plantilla de correo
- `routes/web.php` - Ruta `/sale/enviar-factura-correo`

### Frontend
- `public/assets/js/enviar-factura-correo.js` - JavaScript principal
- `resources/views/sales/index.blade.php` - Vista de ventas (modificada)
- `resources/views/quotations/index.blade.php` - Vista de cotizaciones (modificada)
- `resources/views/quotations/show.blade.php` - Vista de detalle de cotizaciones (modificada)

## Configuración

### 1. Configuración de Correo

La funcionalidad usa **exactamente** la configuración que ya tienes en tu archivo `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=tu_correo@gmail.com
MAIL_PASSWORD=tu_password_app
MAIL_FROM_ADDRESS=tu_correo@gmail.com
MAIL_FROM_NAME=Tu Empresa
```

**No se requiere ninguna configuración adicional.** La funcionalidad lee automáticamente estas variables de entorno.

### 2. Incluir el JavaScript

Agrega el script en las vistas donde quieras usar la funcionalidad:

```html
<script src="{{ asset('assets/js/enviar-factura-correo.js') }}"></script>
```

## Uso

### Opción 1: Botón con datos del cliente

```html
<button type="button" 
        class="btn btn-primary btn-enviar-correo"
        data-factura-id="{{ $sale->id }}"
        data-correo-cliente="{{ $sale->client->email }}"
        data-numero-factura="{{ $sale->numero_control }}"
        title="Enviar factura por correo">
    <i class="ti ti-mail"></i> Enviar Factura
</button>
```

### Opción 2: Botón sin datos del cliente

```html
<button type="button" 
        class="btn btn-success btn-enviar-correo"
        data-factura-id="{{ $sale->id }}"
        data-numero-factura="{{ $sale->numero_control }}">
    <i class="ti ti-paper-plane"></i> Enviar Factura
</button>
```

### Opción 3: Llamada directa desde JavaScript

```javascript
enviarFacturaPorCorreo(123, 'cliente@ejemplo.com', 'FAC-001-2024');
```

### Opción 4: En una tabla de facturas

```html
<button type="button" 
        class="btn btn-sm btn-outline-primary btn-enviar-correo"
        data-factura-id="{{ $sale->id }}"
        data-correo-cliente="{{ $sale->client->email }}"
        data-numero-factura="{{ $sale->numero_control }}"
        title="Enviar factura por correo">
    <i class="ti ti-mail"></i>
</button>
```

## API Endpoint

### POST `/sale/enviar-factura-correo`

**Parámetros:**
- `id_factura` (required): ID de la factura
- `email` (required): Correo electrónico del destinatario
- `nombre_cliente` (optional): Nombre del cliente
- `_token`: Token CSRF

**Respuesta exitosa:**
```json
{
    "success": true,
    "message": "Correo enviado exitosamente",
    "data": {
        "email": "cliente@ejemplo.com",
        "numero_factura": "FAC-001-2024",
        "empresa": "Mi Empresa",
        "cliente": "Juan Pérez"
    }
}
```

**Respuesta de error:**
```json
{
    "success": false,
    "message": "Error al enviar el correo",
    "error": "Detalles del error"
}
```

## Flujo del Proceso

1. **Usuario hace clic** en el botón "Enviar Factura"
2. **SweetAlert2** solicita el correo electrónico (si no está pre-llenado)
3. **Validación** del formato de email
4. **AJAX POST** envía la solicitud al controlador
5. **Controlador valida** los datos de entrada
6. **Consulta la base de datos** para obtener información de la venta
7. **Genera el PDF** usando la función existente
8. **Prepara los datos** para la plantilla de correo
9. **Crea la instancia** de `EnviarFacturaOffline`
10. **Adjunta el PDF** al correo
11. **Envía el correo** usando Laravel Mail
12. **Retorna respuesta JSON** con el resultado
13. **Interfaz muestra** confirmación o error al usuario

## Plantilla de Correo

La plantilla `factura-offline.blade.php` incluye:

- Diseño moderno y responsivo
- Información de la factura
- Datos del cliente
- Nota sobre el adjunto PDF
- Información importante
- Footer con datos de la empresa

## Manejo de Errores

La implementación incluye manejo de errores para:

- **Validación de datos**: Email inválido, factura no encontrada
- **Errores de generación PDF**: Problemas al generar el documento
- **Errores de envío**: Problemas de conexión SMTP
- **Errores del servidor**: Excepciones no controladas

Todos los errores se registran en el log del sistema para debugging.

## Personalización

### Modificar la plantilla de correo

Edita `resources/views/emails/factura-offline.blade.php` para personalizar el diseño del correo.

### Modificar el JavaScript

Edita `public/assets/js/enviar-factura-correo.js` para personalizar la interfaz de usuario.

### Agregar validaciones adicionales

Modifica el método `enviarFacturaPorCorreo()` en `SaleController.php` para agregar validaciones específicas.

## Ejemplos de Implementación

### En vista de ventas (ya implementado)

```php
// En resources/views/sales/index.blade.php
<button type="button"
        class="btn btn-icon btn-outline-success btn-sm me-1 btn-enviar-correo"
        data-factura-id="{{ $sale->id }}"
        data-correo-cliente="{{ $sale->mailClient }}"
        data-numero-factura="{{ $sale->id_doc }}"
        title="Enviar factura por correo">
    <i class="ti ti-mail"></i>
</button>
```

### En vista de cotizaciones (ya implementado)

```php
// En resources/views/quotations/show.blade.php
<button type="button" 
        class="btn btn-outline-primary btn-enviar-correo"
        data-factura-id="{{ $quotation->id }}"
        data-correo-cliente="{{ $quotation->client->email }}"
        data-numero-factura="{{ $quotation->quote_number }}">
    <i class="ti ti-mail me-1"></i>Enviar por Correo
</button>
```

## Vista de Ejemplo

Accede a `/sale/enviar-factura-correo-ejemplo` para ver ejemplos de uso y documentación interactiva.

## Troubleshooting

### Error: "Función no encontrada"
- Verifica que la ruta esté registrada en `routes/web.php`
- Asegúrate de que el controlador tenga el método `enviarFacturaPorCorreo()`

### Error: "Error interno del servidor"
- Revisa los logs en `storage/logs/laravel.log`
- Verifica la configuración de correo en `.env`
- Asegúrate de que la función `genera_pdflocal()` funcione correctamente

### Error: "Email inválido"
- Verifica el formato del email
- Asegúrate de que el campo `email` esté presente en la solicitud

### El correo no se envía
- Verifica la configuración SMTP en `.env`
- Revisa los logs de correo
- Asegúrate de que el servidor tenga acceso a internet

## Contribución

Para agregar mejoras a esta funcionalidad:

1. Modifica el archivo correspondiente
2. Actualiza esta documentación
3. Prueba la funcionalidad
4. Documenta los cambios

## Soporte

Para soporte técnico o preguntas sobre esta implementación, consulta:

- Los logs del sistema en `storage/logs/`
- La documentación de Laravel Mail
- Los ejemplos en la vista `/sale/enviar-factura-correo-ejemplo`
