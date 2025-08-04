# Módulo de Pre-Ventas - Borradores de Facturación

## Descripción

Este módulo permite crear borradores de facturas de manera rápida y eficiente, especialmente diseñado para acumular productos y crear borradores que luego se completen en el módulo de facturación electrónica. Ideal para preparar facturas antes de enviarlas al MH.

## Características Principales

### 🎯 Optimizado para Borradores de Facturación
- **Acumulación de productos**: Agregar múltiples productos para crear borradores
- **Escaneo rápido**: Soporte completo para pistola de código de barras
- **Borradores pendientes**: Lista de borradores para completar en facturación
- **Integración con facturación**: Los borradores aparecen en el módulo de facturación electrónica

### 🔧 Funcionalidades Técnicas
- **Gestión de stock automática**: Actualización en tiempo real del inventario
- **Cálculo de impuestos**: Manejo automático de IVA, exentas y no sujetas
- **Sesiones de venta**: Control de sesiones activas con temporizador y expiración automática
- **Recibos imprimibles**: Generación automática de recibos
- **Estadísticas diarias**: Reportes de ventas del día
- **Gestión de sesiones**: Expiración automática y limpieza de sesiones abandonadas

## Instalación

1. **Verificar que los archivos estén en su lugar:**
   ```
   app/Http/Controllers/PreSaleController.php
   resources/views/presales/index.blade.php
   resources/views/presales/receipt.blade.php
   public/assets/js/presales.js
   routes/web.php (actualizado)
   ```

2. **Verificar que los modelos tengan las relaciones correctas:**
   - `app/Models/Sale.php`
   - `app/Models/Salesdetail.php`

3. **Acceder al módulo:**
   - Ir a **Ventas > Pre-Ventas (Menudeo)** en el menú principal

## Uso del Sistema

### 1. Iniciar Sesión de Venta
1. Hacer clic en **"Nueva Sesión"**
2. Seleccionar la empresa
3. Opcionalmente seleccionar un cliente
4. Confirmar la sesión

### 2. Escanear Productos
1. **Con pistola de código de barras:**
   - Apuntar al código del producto
   - El sistema detectará automáticamente el producto
   - Ajustar cantidad si es necesario
   - Hacer clic en **"Agregar"**

2. **Manual:**
   - Escribir el código del producto
   - Presionar Enter
   - Ajustar cantidad y precio
   - Hacer clic en **"Agregar"**

### 3. Gestionar la Venta
- **Ver productos**: Lista actualizada en tiempo real
- **Remover productos**: Hacer clic en el ícono de basura
- **Modificar cantidades**: Cambiar en el campo cantidad antes de agregar

### 4. Finalizar Venta
1. Revisar los productos y totales
2. Opcionalmente seleccionar cliente
3. Elegir forma de pago
4. Hacer clic en **"Finalizar Venta"**
5. Imprimir recibo si es necesario

## Atajos de Teclado

- **Ctrl + N**: Nueva sesión
- **Ctrl + F**: Finalizar venta
- **Ctrl + C**: Cancelar sesión
- **Enter**: Buscar producto (en campo de código)

## Flujo de Trabajo Recomendado

### Para Ventas Rápidas (Estudiantes)
1. Iniciar sesión sin cliente
2. Escanear productos rápidamente
3. Finalizar venta
4. Imprimir recibo

### Para Ventas con Cliente
1. Iniciar sesión seleccionando cliente
2. Escanear productos
3. Revisar totales
4. Finalizar venta
5. Generar factura si es necesario

## Ventajas del Sistema

### ⚡ Eficiencia
- **Menos clics**: Proceso simplificado para ventas rápidas
- **Escaneo directo**: No necesidad de buscar productos manualmente
- **Acumulación inteligente**: Todos los productos en una sola venta

### 📊 Control
- **Stock en tiempo real**: Actualización automática del inventario
- **Sesiones controladas**: Evita pérdida de datos
- **Estadísticas**: Reportes de ventas del día

### 🎯 Flexibilidad
- **Con o sin cliente**: Adaptable a diferentes tipos de venta
- **Múltiples formas de pago**: Contado, crédito, tarjeta
- **Impresión de recibos**: Para clientes que lo requieran

## Configuración Adicional

### Gestión de Sesiones
El sistema incluye un sistema de expiración automática de sesiones:

- **Duración por defecto**: 4 horas
- **Advertencia**: 30 minutos antes de expirar
- **Limpieza automática**: Comando para limpiar sesiones expiradas

#### Configurar limpieza automática:
```bash
# Limpiar sesiones expiradas manualmente
php artisan presales:cleanup

# Limpiar con tiempo personalizado (ej: 6 horas)
php artisan presales:cleanup --hours=6

# Configurar en cron para limpieza automática (recomendado)
# Agregar al crontab: 0 */2 * * * cd /path/to/project && php artisan presales:cleanup
```

### Personalización de Recibos
Editar `resources/views/presales/receipt.blade.php` para:
- Cambiar el diseño del recibo
- Agregar logo de la empresa
- Modificar información mostrada

### Configuración de Empresas
Asegurarse de que las empresas tengan:
- Nombre completo
- Dirección
- Teléfono
- NIT

### Configuración de Productos
Los productos deben tener:
- Código de barras único
- Precio configurado
- Stock disponible
- Imagen (opcional)

## Solución de Problemas

### Error: "Producto no encontrado"
- Verificar que el código de barras esté registrado
- Confirmar que el producto pertenezca a la empresa seleccionada
- Revisar que el producto esté activo

### Error: "Stock insuficiente"
- Verificar el stock disponible del producto
- Actualizar inventario si es necesario

### Error: "Debe iniciar una sesión primero"
- Hacer clic en "Nueva Sesión"
- Seleccionar empresa
- Confirmar la sesión

### Error: "Sesión expirada"
- La sesión ha expirado automáticamente (4 horas)
- Los productos han sido devueltos al inventario
- Iniciar una nueva sesión

### Error: "Ya tienes una sesión activa"
- El sistema detectó una sesión previa
- Confirmar si deseas continuar con la sesión existente
- O cancelar y crear una nueva

## Soporte

Para problemas técnicos o mejoras:
1. Verificar los logs de Laravel
2. Revisar la consola del navegador
3. Confirmar que todas las rutas estén registradas

## Mejoras Futuras

- [ ] Integración con impresoras térmicas
- [ ] Modo offline para ventas sin internet
- [ ] Sincronización con sistema de inventario
- [ ] Reportes avanzados de ventas
- [ ] Integración con sistema de caja
- [ ] Modo de venta rápida con productos favoritos 
