# ---------- Stage 1: Composer (build dependencies) ----------
FROM composer:2 AS vendor

WORKDIR /app

# Copy file composer lebih dulu agar cache efektif
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress

# Copy source code setelah vendor selesai supaya layer vendor tetap ke-cache
COPY . .

# Optimalkan autoload
RUN composer dump-autoload -o


# ---------- Stage 2: Runtime (FrankenPHP) ----------
FROM dunglas/frankenphp:1-php8.3-alpine

# OS & PHP extensions yang umum untuk Laravel
RUN apk add --no-cache \
    git unzip bash icu-dev oniguruma-dev \
    libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
    mariadb-connector-c-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd intl mbstring pdo_mysql opcache

WORKDIR /app

# Copy seluruh app + vendor dari stage vendor
COPY --from=vendor /app /app

# (Opsional) Jangan jalankan perintah artisan yang butuh APP_KEY/.env saat build
# Cukup pastikan cache bersih agar aman saat runtime
RUN php artisan route:clear && php artisan config:clear && php artisan view:clear \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Caddyfile untuk FrankenPHP
COPY ./deploy/Caddyfile /etc/caddy/Caddyfile

EXPOSE 80
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
