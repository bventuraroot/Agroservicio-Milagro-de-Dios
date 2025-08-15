# Sistema Mejorado de Compras e Inventario

## Descripción General

Este sistema mejorado permite gestionar las compras de manera integral, alimentando automáticamente el inventario y llevando un control detallado de productos con caducidad. El sistema incluye:

- **Gestión de detalles de compras** con productos individuales
- **Control automático de caducidad** basado en configuración de productos
- **Integración automática** entre compras e inventario
- **Alertas y reportes** de productos próximos a vencer
- **Seguimiento de lotes** y fechas de caducidad

## Características Principales

### 1. Sistema de Compras Mejorado

#### Funcionalidades:
- **Formulario dinámico** para agregar múltiples productos
- **Cálculo automático** de subtotales, IVA y totales
- **Validación de datos** en tiempo real
- **Gestión de fechas de caducidad** por producto
- **Números de lote automáticos**

#### Campos de Detalles de Compra:
- Producto seleccionado
- Cantidad
- Precio unitario
- Subtotal calculado
- IVA (13%)
- Total por línea
- Fecha de caducidad (opcional)
- Número de lote (opcional)
- Notas adicionales

### 2. Control de Caducidad

#### Configuración de Productos:
- **`has_expiration`**: Indica si el producto tiene caducidad
- **`expiration_days`**: Días de caducidad desde la compra
- **`expiration_type`**: Tipo de período (días, meses, años)
- **`expiration_notes`**: Notas sobre la caducidad

#### Estados de Caducidad:
- **OK**: Más de 30 días para vencer
- **Advertencia**: Entre 8 y 30 días para vencer
- **Crítico**: 7 días o menos para vencer
- **Vencido**: Ya venció

### 3. Integración con Inventario

#### Alimentación Automática:
- Los productos se agregan automáticamente al inventario
- Actualización de cantidades existentes
- Seguimiento de fechas de caducidad
- Control de lotes

#### Campos de Inventario:
- **`expiration_date`**: Fecha de caducidad
- **`batch_number`**: Número de lote
- **`expiring_quantity`**: Cantidad que vence
- **`expiration_warning_sent`**: Estado de alertas enviadas
- **`last_expiration_check`**: Última verificación

## Estructura de Base de Datos

### Nuevas Tablas y Campos

#### 1. Tabla `purchase_details`
```sql
CREATE TABLE purchase_details (
    id BIGINT PRIMARY KEY,
    purchase_id BIGINT,
    product_id BIGINT,
    quantity INTEGER,
    unit_price DECIMAL(10,2),
    subtotal DECIMAL(10,2),
    tax_amount DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    expiration_date DATE NULL,
    batch_number VARCHAR(255) NULL,
    notes TEXT NULL,
    added_to_inventory BOOLEAN DEFAULT FALSE,
    user_id BIGINT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 2. Campos Agregados a `products`
```sql
ALTER TABLE products ADD COLUMN has_expiration BOOLEAN DEFAULT FALSE;
ALTER TABLE products ADD COLUMN expiration_days INTEGER NULL;
ALTER TABLE products ADD COLUMN expiration_type VARCHAR(50) DEFAULT 'days';
ALTER TABLE products ADD COLUMN expiration_notes TEXT NULL;
```

#### 3. Campos Agregados a `inventory`
```sql
ALTER TABLE inventory ADD COLUMN expiration_date DATE NULL;
ALTER TABLE inventory ADD COLUMN batch_number VARCHAR(255) NULL;
ALTER TABLE inventory ADD COLUMN expiring_quantity INTEGER DEFAULT 0;
ALTER TABLE inventory ADD COLUMN expiration_warning_sent BOOLEAN DEFAULT FALSE;
ALTER TABLE inventory ADD COLUMN last_expiration_check DATE NULL;
```

## Instalación y Configuración

### 1. Ejecutar Migraciones
```bash
php artisan migrate
```

### 2. Configurar Comando de Verificación
Agregar al crontab para verificación automática:
```bash
# Verificar productos próximos a vencer diariamente
0 8 * * * cd /path/to/project && php artisan inventory:check-expiring --notify

# Verificar productos críticos cada 4 horas
0 */4 * * * cd /path/to/project && php artisan inventory:check-expiring --days=7 --notify
```

### 3. Configurar Productos con Caducidad
Para productos que tienen caducidad, configurar:
- `has_expiration = true`
- `expiration_days = [número de días]`
- `expiration_type = 'days'` (o 'months', 'years')

## Uso del Sistema

### 1. Crear una Nueva Compra

#### Paso 1: Acceder al Formulario
- Ir a **Compras > Nueva Compra**
- Llenar información general (proveedor, empresa, fecha, etc.)

#### Paso 2: Agregar Productos
- Hacer clic en **"Agregar Producto"**
- Seleccionar producto del catálogo
- Especificar cantidad y precio
- Configurar fecha de caducidad (si aplica)
- Agregar número de lote (opcional)

#### Paso 3: Guardar Compra
- Revisar totales calculados automáticamente
- Hacer clic en **"Guardar Compra"**
- Los productos se agregarán automáticamente al inventario

### 2. Gestionar Inventario

#### Ver Productos Próximos a Vencer:
- Ir a **Compras > Productos Próximos a Vencer**
- Ver estadísticas por estado
- Filtrar por proveedor, estado o días
- Exportar reportes

#### Ajustar Inventario:
- Desde la vista de productos próximos a vencer
- Hacer clic en el botón de editar
- Ajustar cantidades o fechas

### 3. Comandos de Mantenimiento

#### Verificar Productos Próximos a Vencer:
```bash
# Verificar próximos 30 días
php artisan inventory:check-expiring

# Verificar próximos 7 días con notificaciones
php artisan inventory:check-expiring --days=7 --notify

# Verificar próximos 60 días
php artisan inventory:check-expiring --days=60
```

## API Endpoints

### Compras
- `GET /purchase/products` - Obtener productos disponibles
- `POST /purchase/store` - Crear nueva compra
- `GET /purchase/details/{id}` - Obtener detalles de compra
- `POST /purchase/add-to-inventory/{id}` - Agregar compra al inventario

### Productos Próximos a Vencer
- `GET /purchase/expiring-products` - Productos próximos a vencer
- `GET /purchase/expired-products` - Productos vencidos

## Servicios

### PurchaseInventoryService
Servicio principal para la integración entre compras e inventario:

#### Métodos Principales:
- `addPurchaseToInventory(Purchase $purchase)` - Agregar productos al inventario
- `removePurchaseFromInventory(Purchase $purchase)` - Remover productos del inventario
- `checkExpiringProducts($days = 30)` - Verificar productos próximos a vencer
- `getExpiredProducts()` - Obtener productos vencidos
- `updateExpirationDates(PurchaseDetail $detail)` - Actualizar fechas de caducidad
- `generateBatchNumber(PurchaseDetail $detail)` - Generar número de lote

## Reportes Disponibles

### 1. Productos Próximos a Vencer
- **Críticos**: ≤7 días
- **Advertencia**: 8-30 días
- **Vencidos**: Ya vencidos

### 2. Estadísticas por Estado
- Conteo de productos por categoría
- Filtros por proveedor
- Exportación a Excel

### 3. Reporte de Inventario con Caducidad
- Agrupado por estado de caducidad
- Incluye información de proveedores
- Ordenado por fecha de caducidad

## Configuración de Alertas

### Notificaciones Automáticas
El sistema puede configurarse para enviar alertas cuando:
- Productos están próximos a vencer (≤7 días)
- Productos han vencido
- Stock mínimo alcanzado

### Implementación de Notificaciones
En el método `sendNotifications()` del comando `CheckExpiringProducts`:
- Envío de emails
- Notificaciones push
- Alertas en el sistema

## Mantenimiento

### Limpieza de Datos
```bash
# Limpiar productos vencidos del inventario (cuidado)
php artisan inventory:cleanup-expired

# Actualizar fechas de verificación
php artisan inventory:update-check-dates
```

### Backup de Datos
```bash
# Backup de tablas relacionadas
mysqldump -u user -p database purchases purchase_details inventory products > backup_$(date +%Y%m%d).sql
```

## Solución de Problemas

### Error: "Producto no encontrado"
- Verificar que el producto esté activo en el catálogo
- Confirmar que el proveedor esté asociado correctamente

### Error: "No se pudo agregar al inventario"
- Verificar permisos de escritura en la base de datos
- Revisar logs de Laravel para errores específicos

### Productos no aparecen en reportes de caducidad
- Verificar que tengan fecha de caducidad configurada
- Confirmar que estén en el inventario con cantidad > 0

## Mejoras Futuras

### Funcionalidades Planificadas:
1. **Notificaciones por email** automáticas
2. **Dashboard** con métricas de caducidad
3. **Ajustes de inventario** masivos
4. **Reportes avanzados** con gráficos
5. **Integración con códigos de barras**
6. **App móvil** para verificación en almacén

### Optimizaciones Técnicas:
1. **Caché** para consultas frecuentes
2. **Jobs en cola** para procesamiento asíncrono
3. **API REST** completa
4. **Webhooks** para integraciones externas

## Soporte

Para problemas técnicos o mejoras:
1. Revisar logs de Laravel en `storage/logs/`
2. Verificar configuración de base de datos
3. Confirmar que todas las migraciones se ejecutaron correctamente
4. Revisar permisos de archivos y directorios

---

**Versión**: 1.0  
**Fecha**: Enero 2025  
**Desarrollado por**: Sistema Agroservicio Milagro de Dios
