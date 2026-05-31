# ============================================
# Stage 1: Base PHP + Apache (IaaS Layer)
# Platform: Railway (PaaS)
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

# Enable Apache modules
RUN a2enmod rewrite headers ssl

# Configure Apache
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Configure PHP
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Configure OPcache for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/custom.ini && \
    echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/custom.ini && \
    echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/custom.ini

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/public

# Railway uses dynamic PORT env variable
# Apache listens on PORT env or defaults to 80
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
