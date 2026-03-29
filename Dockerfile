FROM php:8.4-fpm

# System dependencies
RUN apt-get update && apt-get install -y \
    git curl libpq-dev libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_pgsql opcache zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP config
RUN echo "opcache.enable=1\nopcache.memory_consumption=256\nopcache.max_accelerated_files=20000" \
    > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www

# Non-root user
RUN useradd -u 1000 -m appuser
USER appuser

CMD ["php-fpm"]
