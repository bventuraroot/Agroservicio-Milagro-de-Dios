# Configuración Laravel en Hosting Compartido

## Estructura de Directorios Necesaria

Para que tu proyecto Laravel funcione correctamente en la subcarpeta `facturacion/public`, necesitas organizar los archivos de la siguiente manera:

```
tu-dominio.com/
├── .htaccess (archivo principal ya configurado)
├── facturacion/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── artisan
│   ├── composer.json
│   ├── composer.lock
│   ├── .env
│   └── public/
│       ├── .htaccess (ya configurado)
│       ├── index.php
│       ├── css/
│       ├── js/
│       └── assets/
└── [archivos de WordPress]
```

## Pasos para la Configuración

### 1. Crear la estructura de directorios
```bash
mkdir -p facturacion/public
```

### 2. Mover archivos del proyecto Laravel
Mueve TODOS los archivos del proyecto (excepto la carpeta `public`) a la carpeta `facturacion/`:
```bash
# Desde el directorio del proyecto Laravel
cp -r app/ bootstrap/ config/ database/ resources/ routes/ storage/ vendor/ facturacion/
cp artisan composer.json composer.lock .env facturacion/
```

### 3. Mover el contenido de la carpeta public
Mueve TODO el contenido de la carpeta `public` del proyecto Laravel a `facturacion/public/`:
```bash
cp -r public/* facturacion/public/
```

### 4. Modificar el archivo index.php
Edita el archivo `facturacion/public/index.php` y cambia las rutas:

**Busca:**
```php
require_once __DIR__.'/../vendor/autoload.php';
```

**Cambia por:**
```php
require_once __DIR__.'/../vendor/autoload.php';
```

**Busca:**
```php
$app = require_once __DIR__.'/../bootstrap/app.php';
```

**Cambia por:**
```php
$app = require_once __DIR__.'/../bootstrap/app.php';
```

### 5. Configurar permisos
```bash
chmod -R 755 facturacion/
chmod -R 775 facturacion/storage/
chmod -R 775 facturacion/bootstrap/cache/
```

### 6. Configurar el archivo .env
Asegúrate de que tu archivo `.env` en `facturacion/.env` tenga la configuración correcta:

```env
APP_NAME="Tu App"
APP_ENV=production
APP_KEY=tu_app_key_aqui
APP_DEBUG=false
APP_URL=https://tudominio.com/facturacion

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=tu_base_de_datos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña

# Configuración de sesiones y cache
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

## URLs de Acceso

- **WordPress:** `https://tudominio.com/`
- **Laravel:** `https://tudominio.com/facturacion/`

## Verificación

Para verificar que todo funciona correctamente:

1. Accede a `https://tudominio.com/` - debe mostrar WordPress
2. Accede a `https://tudominio.com/facturacion/` - debe mostrar tu aplicación Laravel
3. Verifica que las rutas de Laravel funcionen correctamente

## Notas Importantes

- **Seguridad:** El archivo `.env` NO debe estar en la carpeta `public`
- **Assets:** Los archivos CSS/JS de Laravel deben estar en `facturacion/public/`
- **Storage:** La carpeta `storage` debe tener permisos de escritura
- **Composer:** Si necesitas ejecutar composer, hazlo desde la carpeta `facturacion/`

## Comandos Útiles en el Servidor

Si tienes acceso SSH:

```bash
# Limpiar cache
cd facturacion/
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Troubleshooting

### Error 500
- Verifica permisos de `storage/` y `bootstrap/cache/`
- Revisa el archivo `.env`
- Verifica que las rutas en `index.php` sean correctas

### Assets no cargan
- Verifica que los archivos estén en `facturacion/public/`
- Revisa la configuración de `APP_URL` en `.env`

### Rutas no funcionan
- Verifica que el módulo `mod_rewrite` esté habilitado
- Revisa que el `.htaccess` esté en `facturacion/public/`