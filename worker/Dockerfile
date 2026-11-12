# SPDX-FileCopyrightText: 2026 Vitor Mattos
# SPDX-License-Identifier: AGPL-3.0-or-later
FROM php:8.4-cli
RUN apt-get update && apt-get install -y --no-install-recommends libgmp-dev libpng-dev libonig-dev libffi-dev libzip-dev git unzip \
    && docker-php-ext-install sockets pcntl gmp ffi gd mbstring zip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
COPY server.php ./server.php
RUN mkdir -p /state && chown -R www-data:www-data /state /app
USER www-data
EXPOSE 8080
CMD ["php", "-d", "display_errors=0", "-S", "0.0.0.0:8080", "server.php"]
