FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions in single layer
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libicu-dev \
    libpq-dev \
    poppler-utils \
    pdftk \
    tzdata \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
        xml \
        fileinfo \
        soap \
    && pecl install redis \
    && docker-php-ext-enable redis

# PHP extensions installed in previous layer

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js 18.x (LTS)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Copy configuration files
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copy existing application directory permissions
RUN chown -R www-data:www-data /var/www/html

# Copy application files
COPY --chown=www-data:www-data . /var/www/html

# Set proper permissions
RUN chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy package files first for better caching
COPY --chown=www-data:www-data composer.json composer.lock package*.json ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && npm ci --legacy-peer-deps 2>/dev/null || npm install --legacy-peer-deps

# Build frontend assets
RUN npm run build

# Clean up dev dependencies after build (optional - saves space)
RUN npm prune --production

# Fix ownership after installs
RUN chown -R www-data:www-data /var/www/html/vendor \
    && chown -R www-data:www-data /var/www/html/node_modules || true

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Set entrypoint (run as root to handle permissions, then switch to www-data in entrypoint)
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Start PHP-FPM
CMD ["php-fpm"]

