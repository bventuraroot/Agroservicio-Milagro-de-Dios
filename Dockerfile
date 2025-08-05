FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libgd-dev \
    jpegoptim optipng pngquant gifsicle \
    libzip-dev \
    libicu-dev \
    g++

# Limpiar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Instalar Redis
RUN pecl install redis && docker-php-ext-enable redis

# Obtener Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Crear usuario para aplicación Laravel
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar archivos de configuración primero para cache de layers
COPY composer.json composer.lock ./

# Instalar dependencias de Composer como usuario root
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Instalar Node.js y dependencias
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash -
RUN apt-get install -y nodejs

# Copiar archivos del proyecto
COPY . /var/www

# Copiar archivos de configuración de npm
COPY package*.json ./

# Instalar dependencias de Node.js
RUN npm ci --only=production

# Compilar assets
RUN npm run build

# Configurar permisos
RUN chown -R www:www /var/www
RUN chmod -R 755 /var/www/storage
RUN chmod -R 755 /var/www/bootstrap/cache

# Cambiar a usuario www
USER www

# Exponer puerto 9000
EXPOSE 9000

CMD ["php-fpm"]
