FROM dunglas/frankenphp:1-php8.3-alpine

RUN apk add --no-cache git unzip bash icu-dev oniguruma-dev \
    libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
    mariadb-connector-c-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd intl mbstring pdo_mysql opcache

WORKDIR /app
COPY . /app

RUN composer install --no-dev --prefer-dist --no-interaction --no-progress \
    && php artisan route:clear && php artisan config:clear && php artisan view:clear \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

COPY ./deploy/Caddyfile /etc/caddy/Caddyfile
EXPOSE 80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
