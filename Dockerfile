# -------- Stage 1: Vendor (Composer) --------
FROM php:8.3-cli-alpine AS vendor

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app

# Tools & headers utk build ekstensi (termasuk ZIP)
RUN apk add --no-cache \
    git unzip icu-dev oniguruma-dev \
    libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
    mariadb-connector-c-dev \
    libzip-dev zlib-dev zip \
    $PHPIZE_DEPS

# Aktifkan ekstensi yg dibutuhkan saat composer resolve
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" intl mbstring pdo_mysql gd zip

# Composer dari image resmi
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install vendor dulu supaya cache efektif
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress

# Lalu copy source code
COPY . .
RUN composer dump-autoload -o



# -------- Stage 2: Runtime (FrankenPHP + Caddy) --------
FROM dunglas/frankenphp:1-php8.3-alpine

WORKDIR /app

# Paket & ekstensi runtime (termasuk ZIP)
RUN apk add --no-cache \
    git unzip icu-dev oniguruma-dev \
    libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
    mariadb-connector-c-dev \
    libzip-dev zlib-dev zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd intl mbstring pdo_mysql opcache zip

# Copy app + vendor dari stage vendor
COPY --from=vendor /app /app

# Bersihkan cache & set permission (tanpa butuh .env)
RUN php artisan route:clear && php artisan config:clear && php artisan view:clear \
    && chown -R www-data:www-data storage bootstrap/cache

# Caddyfile
COPY ./deploy/Caddyfile /etc/caddy/Caddyfile

EXPOSE 80
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
