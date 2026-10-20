# Production PHP-FPM runtime for the wb-alex (backend-wb-queue) Laravel app.
# Code is bind-mounted at runtime (see docker-compose.prod.yml), so this image
# only provides the PHP runtime + extensions + tooling needed to build assets
# and run artisan (queue / reverb / scheduler / migrations).
FROM php:8.4-fpm-alpine

# System dependencies:
#   git/curl/unzip/zip  - composer & general tooling
#   nodejs/npm          - `npm ci && npm run build` for Vite assets
#   tzdata              - timezone database
#   *-dev               - headers for the PHP extensions compiled below
RUN apk add --no-cache \
        git curl unzip zip nano less tzdata nodejs npm \
        postgresql-dev libzip-dev icu-dev \
        libpng-dev libjpeg-turbo-dev freetype-dev \
        linux-headers

# PHP extensions required by the app:
#   pdo_pgsql/pgsql - PostgreSQL (DB_CONNECTION=pgsql)
#   bcmath,zip,intl - framework / backpack deps
#   gd              - image handling
#   pcntl           - required by queue:work, reverb:start, schedule:work
#   opcache,exif    - performance / misc
#   redis (pecl)    - QUEUE_CONNECTION=redis + CACHE/SESSION redis
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo pdo_pgsql pgsql bcmath zip intl gd pcntl opcache exif \
    && pecl install redis \
    && docker-php-ext-enable redis

# Composer (for `composer install --no-dev` on the host-mounted code)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Production PHP settings
COPY php.ini /usr/local/etc/php/conf.d/99-prod.ini

# Container entrypoint (perms + optional migrate) then execs the service command
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Make the project .env / environment visible to artisan run through PHP-FPM
RUN echo 'clear_env = no' >> /usr/local/etc/php-fpm.d/www.conf

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
