FROM ubuntu:20.04 as base

ENV DEBIAN_FRONTEND=noninteractive

# Create non-root user early
RUN groupadd -r appuser && useradd -r -g appuser -m -d /home/appuser appuser

# Install system packages and configure timezone
RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y \
        tzdata \
        apt-utils \
        software-properties-common \
        apt-transport-https \
        build-essential \
        curl \
        wget \
        git \
        unzip \
        zip \
        vim \
        net-tools \
        mysql-client \
    && ln -fs /usr/share/zoneinfo/Asia/Kolkata /etc/localtime \
    && dpkg-reconfigure tzdata \
    && add-apt-repository ppa:ondrej/php \
    && apt-get update \
    && apt-get install -y \
        php7.4-fpm=7.4.* \
        php7.4-cli=7.4.* \
        php7.4-mysqli=7.4.* \
        php7.4-curl=7.4.* \
        php7.4-mbstring=7.4.* \
        php7.4-gd=7.4.* \
        php7.4-zip=7.4.* \
        php7.4-xml=7.4.* \
        php7.4-json=7.4.* \
        php7.4-bcmath=7.4.* \
        php7.4-intl=7.4.* \
        php7.4-soap=7.4.* \
        php7.4-xsl=7.4.* \
        nginx=1.18.* \
        nodejs=10.* \
        npm=6.* \
        python3-pip \
        imagemagick \
        ffmpeg \
        poppler-utils \
        pdftk \
        s3cmd \
        p7zip-full \
    && rm -rf /var/lib/apt/lists/*

# Install Composer with specific version
RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php \
    && php7.4 /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --version=2.2.18 \
    && rm /tmp/composer-setup.php

# Set up PHP alternatives
RUN update-alternatives --install /usr/bin/php php /usr/bin/php7.4 1

# Configure PHP-FPM
ENV PHP_INI_DIR='/etc/php/7.4/fpm'
RUN sed -i 's/post_max_size = 8M/post_max_size = 1024M/' "${PHP_INI_DIR}/php.ini" \
    && sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 1024M/' "${PHP_INI_DIR}/php.ini" \
    && sed -i 's/memory_limit = 128M/memory_limit = 2048M/' "${PHP_INI_DIR}/php.ini" \
    && sed -i 's/max_execution_time = 30/max_execution_time = 300/' "${PHP_INI_DIR}/php.ini" \
    && sed -i 's/max_input_time = 60/max_input_time = 300/' "${PHP_INI_DIR}/php.ini"

# Configure PHP-FPM pool
RUN sed -i 's/listen = .*/listen = \/run\/php\/php7.4-fpm.sock/' /etc/php/7.4/fpm/pool.d/www.conf \
    && sed -i 's/;listen.owner = .*/listen.owner = www-data/' /etc/php/7.4/fpm/pool.d/www.conf \
    && sed -i 's/;listen.group = .*/listen.group = www-data/' /etc/php/7.4/fpm/pool.d/www.conf \
    && sed -i 's/;listen.mode = .*/listen.mode = 0660/' /etc/php/7.4/fpm/pool.d/www.conf

# Remove default nginx config
RUN rm -f /etc/nginx/sites-enabled/default \
    && sed -i "\|include /etc/nginx/sites-enabled/\*;|d" "/etc/nginx/nginx.conf"

# Set working directory
WORKDIR /var/www

# Copy dependency files first (for better caching)
COPY --chown=appuser:appuser composer.json composer.lock* ./
COPY --chown=appuser:appuser package.json package-lock.json* ./

# Install PHP dependencies
USER appuser
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Install Node.js dependencies
RUN npm ci --only=production

# Switch back to root for system operations
USER root

# Install phpMyAdmin
RUN curl -L https://files.phpmyadmin.net/phpMyAdmin/5.1.4/phpMyAdmin-5.1.4-all-languages.tar.gz -o pma.tar.gz \
    && mkdir -p /var/www/phpmyadmin \
    && tar -xzf pma.tar.gz -C /var/www/phpmyadmin --strip-components=1 \
    && rm pma.tar.gz \
    && chown -R www-data:www-data /var/www/phpmyadmin

# Copy application code (excluding sensitive files via .dockerignore)
COPY --chown=www-data:www-data . .

# Create necessary directories and set permissions
RUN mkdir -p uploads logs cache sessions tmp \
    && chown -R www-data:www-data uploads/ logs/ cache/ sessions/ tmp/ \
    && chmod -R 755 uploads/ logs/ cache/ sessions/ tmp/

# Create PHP-FPM run directory
RUN mkdir -p /run/php \
    && chown www-data:www-data /run/php

# Copy and set up entrypoint script
COPY scripts/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

EXPOSE 80

CMD ["/usr/local/bin/docker-entrypoint.sh"]