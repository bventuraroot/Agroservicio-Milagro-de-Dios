# Docker Setup - Agroservicio Milagro de Dios

Este proyecto está configurado para ejecutarse con Docker y Docker Compose.

## Requisitos Previos

- Docker Desktop instalado
- Docker Compose instalado
- Git (para clonar el repositorio)

## Servicios Incluidos

- **app**: Aplicación Laravel con PHP 8.2-FPM
- **nginx**: Servidor web Nginx
- **db**: Base de datos MySQL 8.0
- **redis**: Cache Redis
- **phpmyadmin**: Interfaz web para administrar MySQL

## Puertos

- **8000**: Aplicación Laravel (nginx)
- **8080**: PHPMyAdmin
- **3307**: MySQL (para conexiones externas)

## Inicio Rápido

### Opción 1: Script Automático (Recomendado)

```bash
# Dar permisos de ejecución al script
chmod +x docker-start.sh

# Ejecutar configuración automática
./docker-start.sh
```

### Opción 2: Manual

```bash
# 1. Crear archivo .env
cp .env.example .env

# 2. Construir y levantar contenedores
docker-compose up -d --build

# 3. Generar key de aplicación
docker-compose exec app php artisan key:generate

# 4. Ejecutar migraciones
docker-compose exec app php artisan migrate

# 5. Ejecutar seeders (opcional)
docker-compose exec app php artisan db:seed
```

## Comandos Útiles

### Gestión de Contenedores

```bash
# Ver estado de los servicios
docker-compose ps

# Ver logs en tiempo real
docker-compose logs -f

# Ver logs de un servicio específico
docker-compose logs -f app

# Detener servicios
docker-compose down

# Reiniciar servicios
docker-compose restart

# Reconstruir contenedores
docker-compose up -d --build
```

### Acceso a Contenedores

```bash
# Acceder al contenedor de la aplicación
docker-compose exec app bash

# Acceder al contenedor de la base de datos
docker-compose exec db mysql -u root -p

# Ejecutar comandos Artisan
docker-compose exec app php artisan migrate
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:cache
```

### Base de Datos

```bash
# Ejecutar migraciones
docker-compose exec app php artisan migrate

# Ejecutar migraciones con seeders
docker-compose exec app php artisan migrate:refresh --seed

# Crear nueva migración
docker-compose exec app php artisan make:migration nombre_migracion

# Acceder a MySQL CLI
docker-compose exec db mysql -u laravel -p agroservicio
```

### Cache y Optimización

```bash
# Limpiar todas las cachés
docker-compose exec app php artisan optimize:clear

# Optimizar aplicación para producción
docker-compose exec app php artisan optimize

# Limpiar cache específico
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

## Configuración de Base de Datos

### Credenciales por defecto:

- **Host**: db (dentro de Docker) / localhost:3307 (desde fuera)
- **Base de datos**: agroservicio
- **Usuario**: laravel
- **Contraseña**: root
- **Usuario root**: root
- **Contraseña root**: root

### PHPMyAdmin:

- **URL**: http://localhost:8080
- **Usuario**: root
- **Contraseña**: root

## Estructura de Archivos Docker

```
proyecto/
├── docker/
│   └── nginx/
│       └── conf.d/
│           └── app.conf          # Configuración de nginx
├── docker-compose.yml            # Orquestación de servicios
├── Dockerfile                    # Imagen de la aplicación PHP
├── .env.example                  # Variables de entorno de ejemplo
└── docker-start.sh              # Script de inicio automático
```

## Solución de Problemas

### Error de permisos

```bash
# Arreglar permisos de storage y cache
docker-compose exec app chown -R www:www /var/www/storage
docker-compose exec app chown -R www:www /var/www/bootstrap/cache
docker-compose exec app chmod -R 755 /var/www/storage
docker-compose exec app chmod -R 755 /var/www/bootstrap/cache
```

### Base de datos no conecta

```bash
# Verificar que el servicio de DB está corriendo
docker-compose ps

# Ver logs de la base de datos
docker-compose logs db

# Reiniciar servicio de base de datos
docker-compose restart db
```

### Reinstalar dependencias

```bash
# Entrar al contenedor
docker-compose exec app bash

# Reinstalar dependencias de Composer
composer install

# Reinstalar dependencias de npm
npm install
npm run build
```

### Limpiar todo y empezar de nuevo

```bash
# Detener y eliminar contenedores
docker-compose down -v

# Eliminar imágenes construidas
docker-compose build --no-cache

# Volver a levantar
docker-compose up -d
```

## Variables de Entorno Importantes

En tu archivo `.env`, asegúrate de tener estas configuraciones:

```env
# Base de datos (para Docker)
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=agroservicio
DB_USERNAME=laravel
DB_PASSWORD=root

# Redis (para Docker)
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# URL de la aplicación
APP_URL=http://localhost:8000
```

## Producción

Para usar en producción, modifica:

1. Cambia `APP_ENV=production` en `.env`
2. Cambia `APP_DEBUG=false` en `.env`
3. Configura variables de correo y otros servicios
4. Usa un proxy reverso como Traefik o nginx externo
5. Configura certificados SSL
