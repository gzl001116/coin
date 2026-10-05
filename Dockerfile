FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json ./
RUN composer install --no-interaction --prefer-dist --no-progress --optimize-autoloader

COPY . .
EXPOSE 8000
CMD ["php","artisan","serve","--host=0.0.0.0","--port=8000"]
