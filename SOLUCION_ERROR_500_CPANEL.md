# 🚨 Solución Error 500 en cPanel - Aplicación Laravel

## 🔍 Diagnóstico Inicial

El error 500 (Error Interno del Servidor) es muy común al migrar aplicaciones Laravel a cPanel. Sigamos estos pasos **en orden** para solucionarlo:

---

## 📋 Lista de Verificación

### ✅ **PASO 1: Revisar los Logs de Error**

**Ubicaciones de logs en cPanel:**
- `public_html/storage/logs/laravel.log`
- Panel de cPanel → **Error Logs**
- `public_html/error_log`

```bash
# Si tienes acceso SSH, ejecuta:
tail -f ~/public_html/storage/logs/laravel.log
```

---

### ✅ **PASO 2: Configurar el Archivo .env**

**Crear/editar el archivo `.env` en la raíz del proyecto:**

```env
# CONFIGURACIÓN BÁSICA
APP_NAME="RomaCopies"
APP_ENV=production
APP_KEY=base64:TU_CLAVE_AQUI
APP_DEBUG=false
APP_URL=https://tudominio.com

# BASE DE DATOS (configura según tu cPanel)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=tu_base_de_datos
DB_USERNAME=tu_usuario_db
DB_PASSWORD=tu_password_db

# CONFIGURACIÓN DE SESIONES
SESSION_DRIVER=file
SESSION_LIFETIME=480
SESSION_ENCRYPT=false

# CONFIGURACIÓN DE CACHE
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

# CONFIGURACIÓN DE MAIL
MAIL_MAILER=smtp
MAIL_HOST=tu_servidor_smtp
MAIL_PORT=587
MAIL_USERNAME=tu_email
MAIL_PASSWORD=tu_password_email
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tudominio.com
MAIL_FROM_NAME="${APP_NAME}"

# CONFIGURACIÓN DE ARCHIVOS
FILESYSTEM_DISK=local
```

> **⚠️ IMPORTANTE:** Cambia `APP_DEBUG=false` en producción para evitar mostrar errores sensibles.

---

### ✅ **PASO 3: Configurar Permisos de Archivos**

**En el File Manager de cPanel o por SSH:**

```bash
# Permisos para carpetas
chmod 755 public_html/
chmod -R 755 public_html/bootstrap/
chmod -R 775 public_html/storage/
chmod -R 775 public_html/bootstrap/cache/

# Permisos para archivos
chmod 644 public_html/.env
chmod 644 public_html/.htaccess
find public_html/storage/ -type f -exec chmod 664 {} \;
find public_html/bootstrap/cache/ -type f -exec chmod 664 {} \;
```

**En File Manager de cPanel:**
1. Selecciona la carpeta `storage/` → Clic derecho → **Permissions**
2. Marca: `Read`, `Write`, `Execute` para **Owner** y **Group**
3. Aplica a todas las subcarpetas ✅
4. Repite con `bootstrap/cache/`

---

### ✅ **PASO 4: Instalar Dependencias (Composer)**

**Si tienes acceso SSH:**
```bash
cd public_html/
composer install --no-dev --optimize-autoloader
```

**Si NO tienes SSH:**
1. Descarga el proyecto completo con `vendor/` desde tu entorno local
2. Sube TODO el contenido via File Manager o FTP
3. O contacta soporte de tu hosting para que ejecuten `composer install`

---

### ✅ **PASO 5: Generar APP_KEY**

**Con SSH:**
```bash
cd public_html/
php artisan key:generate
```

**Sin SSH:**
1. En tu computadora local, ejecuta: `php artisan key:generate`
2. Copia la clave generada del archivo `.env` local
3. Pégala en el `.env` del servidor

---

### ✅ **PASO 6: Crear Enlaces Simbólicos**

```bash
# Con SSH
cd public_html/
php artisan storage:link
```

**Sin SSH:**
Crea manualmente la carpeta `public/storage/` y copia el contenido de `storage/app/public/`

---

### ✅ **PASO 7: Configurar la Base de Datos**

**En cPanel:**
1. **MySQL® Databases** → Crear nueva base de datos
2. Crear usuario y asignarlo a la base de datos
3. Importar tu archivo `.sql` via **phpMyAdmin**
4. Actualizar credenciales en `.env`

**Ejecutar migraciones (si es necesario):**
```bash
php artisan migrate --force
php artisan db:seed --force
```

---

### ✅ **PASO 8: Limpiar Cachés**

```bash
# Con SSH
cd public_html/
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
```

**Sin SSH:**
Elimina manualmente estas carpetas:
- `bootstrap/cache/config.php`
- `bootstrap/cache/routes.php`
- `bootstrap/cache/packages.php`
- `bootstrap/cache/services.php`

---

### ✅ **PASO 9: Verificar Versión de PHP**

**En cPanel:**
1. **Software → PHP Selector** o **MultiPHP Manager**
2. Selecciona **PHP 8.0** o superior
3. Asegúrate que estas extensiones estén habilitadas:
   - `pdo_mysql`
   - `mbstring`
   - `openssl`
   - `tokenizer`
   - `xml`
   - `gd`
   - `zip`

---

### ✅ **PASO 10: Configurar Document Root**

**Si instalaste en subdirectorio:**
1. En cPanel → **Subdomains** o **Addon Domains**
2. Cambia **Document Root** para que apunte a `public_html/tu-proyecto/public/`

**O mover archivos:**
```bash
# Mover contenido de public/ a public_html/
# Y el resto del proyecto a una carpeta fuera de public_html/
```

---

## 🔧 Soluciones a Problemas Específicos

### ❌ **Error: "No input file specified"**
```bash
# Agregar al .htaccess:
RewriteRule ^(.*)$ index.php [QSA,L]
```

### ❌ **Error: "Class not found"**
```bash
# Regenerar autoloader
composer dump-autoload --optimize
```

### ❌ **Error: "Storage link already exists"**
```bash
# Eliminar enlace existente
rm public/storage
php artisan storage:link
```

### ❌ **Error: Base de datos no conecta**
1. Verifica que el host sea `localhost`
2. Confirma el nombre de la DB (suele tener prefijo: `usuario_nombredb`)
3. Prueba conexión desde **phpMyAdmin**

### ❌ **Error: "Please provide a valid cache path"**
```bash
# Crear carpetas faltantes
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
```

---

## 🧪 Verificación Final

**Crea un archivo de prueba `test.php` en `public/`:**
```php
<?php
// test.php
echo "PHP funciona: " . PHP_VERSION . "<br>";
echo "Laravel cargó: ";
try {
    require_once '../vendor/autoload.php';
    $app = require_once '../bootstrap/app.php';
    echo "✅ OK";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
```

Visita: `https://tudominio.com/test.php`

---

## 🆘 Si Nada Funciona

### **Contactar Soporte del Hosting**
Proporciona esta información:
1. **Error específico** de los logs
2. **Versión de PHP** requerida (8.0+)
3. **Extensiones PHP** necesarias
4. **Permisos** requeridos para `storage/`

### **Debugging Adicional**
```php
// Agregar temporalmente al inicio de public/index.php:
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### **Verificar .htaccess**
Si sigue fallando, renombra `.htaccess` a `.htaccess-backup` y prueba.

---

## 📞 Archivos de Configuración Específicos

**Tu aplicación necesita estas configuraciones especiales:**

### `php.ini` (en public/)
```ini
session.gc_maxlifetime = 28800
session.cookie_lifetime = 28800
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 512M
```

### `.env` adicional para tu aplicación:
```env
# Configuraciones específicas para facturación electrónica
FACTURACION_ELECTRONICA_ENABLED=true
CONTINGENCY_MODE=false
INVOICE_PREFIX=FAC
```

---

## ✅ Lista de Verificación Final

- [ ] Archivo `.env` configurado correctamente
- [ ] Permisos 775 en `storage/` y `bootstrap/cache/`
- [ ] `vendor/` instalado con todas las dependencias
- [ ] `APP_KEY` generada
- [ ] Base de datos creada y migrada
- [ ] `storage:link` ejecutado
- [ ] Cachés limpiados
- [ ] PHP 8.0+ configurado
- [ ] Document Root apunta a `/public/`
- [ ] Error logs revisados

---

**🎉 ¡Una vez completados todos los pasos, tu aplicación Laravel debería funcionar correctamente en cPanel!**

Si sigues teniendo problemas, comparte el contenido específico de los **error logs** para ayuda más dirigida. 
