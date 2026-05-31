# ============================================
# Dockerfile — ContentHub
# Platform : Railway (PaaS)
# PHP 8.2 + Apache + PostgreSQL Driver
# ============================================
FROM php:8.2-apache

LABEL maintainer="your@email.com"
LABEL description="PHP CRUD App with PostgreSQL on Railway"

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    opcache

# Enable Apache modules (rewrite, headers, ssl)
# MPM conflict diselesaikan di entrypoint.sh saat runtime
RUN a2enmod rewrite headers

# Copy Apache config
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Copy PHP config
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Set working directory
WORKDIR /var/www/html

# Copy semua file aplikasi
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Copy entrypoint (handle MPM fix + dynamic PORT)
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]