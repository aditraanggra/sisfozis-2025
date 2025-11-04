# -------- Stage 1: Vendor (Composer) --------
# Pakai PHP CLI Alpine + aktifkan ekstensi yang dibutuhkan Composer (intl)
FROM php:8.3-cli-alpine AS vendor

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app

# Tools dan header untuk build ekstensi
RUN apk add --no-cache \
    git unzip icu-dev oniguruma-dev \
    libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
    mariadb-connector-c-dev $PHPIZE_DEPS

# Aktifkan ekstensi yang dibutuhkan saat resolve dependency
# (intl diperlukan oleh paket Filament)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) intl mbstring pdo_mysql gd

# Ambil binary Composer dari image resmi
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy file composer dulu agar cache efisien
COPY composer.json composer.lock ./

# Install vendor (tanpa dev)
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress

# Baru copy source code lainnya
COPY . .

# Optimalkan autoload
RUN composer dump-autoload -o



# -------- Stage 2: Runtime (FrankenPHP + Caddy) --------
FROM dunglas/frankenphp:1-php8.3-alpine

WORKDIR /app

# Pasang ekstensi PHP untuk runtime Laravel
RUN apk add --no-cache \
    git unzip icu-dev oniguruma-dev \
    libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
    mariadb-connector-c-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd intl mbstring pdo_mysql opcache

# Copy seluruh app (termasuk vendor) dari stage vendor
COPY --from=vendor /app /app

# JANGAN jalankan artisan yang butuh .env/APP_KEY di build time
# Hanya pastikan cache bersih & permission benar
RUN php artisan route:clear && php artisan config:clear && php artisan view:clear \
    && chown -R www-data:www-data storage bootstrap/cache

# Caddyfile untuk FrankenPHP
COPY ./deploy/Caddyfile /etc/caddy/Caddyfile

EXPOSE 80
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
