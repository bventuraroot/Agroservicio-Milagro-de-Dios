FROM php:8.2.13-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Limpiar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Obtener Composer
COPY --from=composer:2.6.5 /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar archivos del proyecto
COPY . /var/www

# Instalar dependencias de Composer
RUN composer install

# Instalar dependencias de Node.js
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash -
RUN apt-get install -y nodejs
RUN npm install

# Generar key de la aplicación
RUN php artisan key:generate

# Permisos
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Cambia el DocumentRoot a /var/www/html/public
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Habilita mod_rewrite
RUN a2enmod rewrite

# Copia el .htaccess (opcional, si no se copia con el volumen)
COPY public/.htaccess /var/www/html/public/.htaccess

# Exponer puerto 80
EXPOSE 80

CMD ["apache2-foreground"]
