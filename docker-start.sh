#!/bin/bash

echo "=== Configurando Agroservicio Milagro de Dios con Docker ==="

# Verificar si Docker está instalado
if ! command -v docker &> /dev/null; then
    echo "❌ Docker no está instalado. Por favor instala Docker primero."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose no está instalado. Por favor instala Docker Compose primero."
    exit 1
fi

# Crear archivo .env si no existe
if [ ! -f .env ]; then
    echo "📋 Creando archivo .env desde .env.example..."
    cp .env.example .env
    echo "✅ Archivo .env creado"
else
    echo "📋 Archivo .env ya existe"
fi

# Construir y levantar contenedores
echo "🐳 Construyendo contenedores Docker..."
docker-compose build --no-cache

echo "🚀 Levantando servicios..."
docker-compose up -d

# Esperar a que la base de datos esté lista
echo "⏳ Esperando que la base de datos esté lista..."
sleep 15

# Generar key de aplicación si no existe
echo "🔑 Generando key de aplicación Laravel..."
docker-compose exec app php artisan key:generate --force

# Ejecutar migraciones
echo "📊 Ejecutando migraciones..."
docker-compose exec app php artisan migrate --force

# Ejecutar seeders si existen
echo "🌱 Ejecutando seeders..."
docker-compose exec app php artisan db:seed --force

# Optimizar aplicación
echo "⚡ Optimizando aplicación..."
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Configurar permisos
echo "🔧 Configurando permisos..."
docker-compose exec app chown -R www:www /var/www/storage
docker-compose exec app chown -R www:www /var/www/bootstrap/cache

echo ""
echo "✅ ¡Configuración completada!"
echo ""
echo "🌐 Aplicación disponible en: http://localhost:8000"
echo "🗄️  PHPMyAdmin disponible en: http://localhost:8080"
echo "   Usuario: root"
echo "   Contraseña: root"
echo ""
echo "📋 Comandos útiles:"
echo "   docker-compose logs -f app     # Ver logs de la aplicación"
echo "   docker-compose exec app bash   # Entrar al contenedor"
echo "   docker-compose down           # Detener servicios"
echo "   docker-compose up -d          # Levantar servicios"
echo ""
