FROM php:8.1-cli

# install-php-extensions makes building PHP extensions (with their system
# library deps) reliable across base images.
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions \
        pdo_mysql \
        mbstring \
        bcmath \
        gd \
        zip \
        intl \
        gmp \
        exif \
        soap \
        pcntl \
        sockets \
        redis \
    && apt-get update && apt-get install -y --no-install-recommends unzip git \
    && rm -rf /var/lib/apt/lists/*

# Composer (used for any future dependency work; vendor/ is already vendored).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

EXPOSE 8000

# This app keeps index.php at the project root (shared-hosting layout), so the
# built-in server is pointed at the root via a custom router instead of
# `artisan serve` (which assumes public/index.php).
CMD ["php", "-S", "0.0.0.0:8000", "-t", "/app", "/app/router.php"]
