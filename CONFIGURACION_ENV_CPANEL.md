# 📋 Configuración del archivo .env para cPanel

## 📝 Crea este archivo como `.env` en la raíz de tu proyecto

```env
# ================================================================
# CONFIGURACIÓN BÁSICA DE LARAVEL
# ================================================================
APP_NAME="RomaCopies"
APP_ENV=production
APP_KEY=base64:GENERAR_NUEVA_CLAVE_CON_ARTISAN_KEY_GENERATE
APP_DEBUG=false
APP_URL=https://tudominio.com

# ================================================================
# BASE DE DATOS - MYSQL (CONFIGURAR SEGÚN TU CPANEL)
# ================================================================
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=usuario_nombredb
DB_USERNAME=usuario_db
DB_PASSWORD=password_seguro

# ================================================================
# CONFIGURACIÓN DE SESIONES
# ================================================================
SESSION_DRIVER=file
SESSION_LIFETIME=480
SESSION_ENCRYPT=false
SESSION_EXPIRE_ON_CLOSE=false

# ================================================================
# CONFIGURACIÓN DE CACHE Y COLAS
# ================================================================
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

# ================================================================
# CONFIGURACIÓN DE CORREO ELECTRÓNICO
# ================================================================
MAIL_MAILER=smtp
MAIL_HOST=tu-servidor-smtp.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@tudominio.com
MAIL_PASSWORD=tu_password_email
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tudominio.com
MAIL_FROM_NAME="${APP_NAME}"

# ================================================================
# CONFIGURACIÓN DE ARCHIVOS Y ALMACENAMIENTO
# ================================================================
FILESYSTEM_DISK=local

# ================================================================
# CONFIGURACIONES ESPECÍFICAS DE FACTURACIÓN ELECTRÓNICA
# ================================================================
FACTURACION_ELECTRONICA_ENABLED=true
CONTINGENCY_MODE=false
INVOICE_PREFIX=FAC
CREDIT_NOTE_PREFIX=CRF

# URLs del Ministerio de Hacienda (AMBIENTE DE PRUEBAS)
MH_URL_AUTENTICACION_PRUEBAS=https://apitest.dtes.mh.gob.sv/seguridad/auth
MH_URL_ENVIO_PRUEBAS=https://apitest.dtes.mh.gob.sv/fesv/recepciondte

# URLs del Ministerio de Hacienda (AMBIENTE DE PRODUCCIÓN)
MH_URL_AUTENTICACION_PRODUCCION=https://api.dtes.mh.gob.sv/seguridad/auth
MH_URL_ENVIO_PRODUCCION=https://api.dtes.mh.gob.sv/fesv/recepciondte

# ================================================================
# CONFIGURACIÓN DE LOGS
# ================================================================
LOG_CHANNEL=stack
LOG_LEVEL=error

# ================================================================
# CONFIGURACIÓN DE BROADCAST
# ================================================================
BROADCAST_DRIVER=log
```

## 🔑 Pasos para configurar:

### 1. **APP_KEY**
```bash
# Generar nueva clave:
php artisan key:generate
```

### 2. **Base de Datos (cPanel)**
- **DB_HOST**: Casi siempre es `localhost`
- **DB_DATABASE**: Generalmente es `usuario_nombredb` (con prefijo)
- **DB_USERNAME**: Tu usuario de MySQL en cPanel
- **DB_PASSWORD**: Contraseña de MySQL en cPanel

### 3. **URL de la aplicación**
```env
APP_URL=https://tudominio.com
# o
APP_URL=https://tudominio.com/carpeta-proyecto
```

### 4. **Configuración de correo**
Obtener datos del proveedor de hosting para:
- MAIL_HOST
- MAIL_PORT
- MAIL_USERNAME
- MAIL_PASSWORD

## ⚠️ IMPORTANTE:
- **Cambia APP_DEBUG=false** en producción
- **Nunca subas el archivo .env** a repositorios públicos
- **Verifica que las credenciales de DB sean correctas** 
