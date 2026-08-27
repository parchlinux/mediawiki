# ==============================================================================
# Parch Linux MediaWiki - Production Container Image (Alpine Edition)
# Ultra-lightweight multi-stage Dockerfile with PHP 8.3 FPM, APCu, Redis, Pygments & Extensions
# ==============================================================================

FROM php:8.3-fpm-alpine AS base

LABEL maintainer="Parch Linux Core Team <info@parchlinux.com>"
LABEL description="Ultra-lightweight containerized MediaWiki instance tailored for Parch Linux Wiki"

ENV MW_INSTALL_PATH=/var/www/html

# Install essential runtime libraries and system tools
RUN apk add --no-cache \
    bash \
    git \
    curl \
    unzip \
    zip \
    icu-libs \
    libpng \
    libjpeg-turbo \
    libwebp \
    freetype \
    libzip \
    oniguruma \
    libxml2 \
    libxslt \
    python3 \
    py3-pygments \
    lua5.1 \
    pandoc \
    mariadb-client \
    netcat-openbsd \
    ca-certificates

# Install build dependencies, compile PHP & PECL extensions, and strip build packages
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    libxslt-dev \
    linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        intl \
        gd \
        mysqli \
        pdo_mysql \
        opcache \
        zip \
        bcmath \
        sockets \
        pcntl \
        soap \
        xsl \
        calendar \
        exif \
    && pecl install apcu redis \
    && docker-php-ext-enable apcu redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear

# Copy Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure PHP settings for MediaWiki production
RUN { \
        echo 'memory_limit = 512M'; \
        echo 'upload_max_filesize = 128M'; \
        echo 'post_max_size = 128M'; \
        echo 'max_execution_time = 300'; \
        echo 'date.timezone = UTC'; \
        echo 'expose_php = Off'; \
    } > /usr/local/etc/php/conf.d/mediawiki.ini \
    && { \
        echo 'opcache.enable = 1'; \
        echo 'opcache.enable_cli = 1'; \
        echo 'opcache.memory_consumption = 256'; \
        echo 'opcache.interned_strings_buffer = 32'; \
        echo 'opcache.max_accelerated_files = 30000'; \
        echo 'opcache.revalidate_freq = 2'; \
        echo 'opcache.save_comments = 1'; \
    } > /usr/local/etc/php/conf.d/opcache.ini \
    && { \
        echo 'apc.enable_cli = 1'; \
        echo 'apc.shm_size = 128M'; \
    } > /usr/local/etc/php/conf.d/apcu.ini

WORKDIR ${MW_INSTALL_PATH}

# Copy MediaWiki core codebase
COPY . ${MW_INSTALL_PATH}/

# Copy helper and entrypoint scripts
COPY docker/install-extensions.sh /usr/local/bin/install-extensions.sh
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/install-extensions.sh /usr/local/bin/entrypoint.sh

# Download and initialize Parch curated extensions & skins
RUN /usr/local/bin/install-extensions.sh

# Ensure persistent directories exist and set appropriate permissions
RUN mkdir -p ${MW_INSTALL_PATH}/images ${MW_INSTALL_PATH}/cache \
    && chown -R www-data:www-data ${MW_INSTALL_PATH}/images ${MW_INSTALL_PATH}/cache

VOLUME ["/var/www/html/images", "/var/www/html/cache"]

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
