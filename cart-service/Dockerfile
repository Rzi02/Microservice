
FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev unzip git curl \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY . .

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm /tmp/composer-setup.php \
    && composer install --no-interaction --prefer-dist --optimize-autoloader

EXPOSE 3001

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=3001"]
