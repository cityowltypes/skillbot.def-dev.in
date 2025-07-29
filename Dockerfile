FROM ubuntu:20.04

ENV DEBIAN_FRONTEND=noninteractive

# Install system packages and configure timezone
RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y \
        tzdata \
        software-properties-common \
        curl \
        mysql-client \
        git \
        unzip \
        zip \
        vim \
    && ln -fs /usr/share/zoneinfo/Asia/Kolkata /etc/localtime \
    && dpkg-reconfigure tzdata \
    && add-apt-repository ppa:ondrej/php \
    && apt-get update \
    && apt-get install -y \
        php7.4-fpm \
        php7.4-cli \
        php7.4-mysqli \
        php7.4-curl \
        php7.4-mbstring \
        php7.4-gd \
        php7.4-zip \
        php7.4-xml \
        php7.4-json \
        php7.4-bcmath \
        php7.4-intl \
        php7.4-soap \
        php7.4-xsl \
        nginx \
        nodejs \
        npm \
        python3-pip \
        imagemagick \
        ffmpeg \
        poppler-utils \
        pdftk \
        s3cmd \
        p7zip-full \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
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

# Copy application code
COPY . .

# Install only PHP dependencies during build
RUN if [ -f "composer.json" ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs; \
    fi

# Install phpMyAdmin
RUN curl -L https://files.phpmyadmin.net/phpMyAdmin/5.1.4/phpMyAdmin-5.1.4-all-languages.tar.gz -o pma.tar.gz \
    && mkdir -p /var/www/phpmyadmin \
    && tar -xzf pma.tar.gz -C /var/www/phpmyadmin --strip-components=1 \
    && rm pma.tar.gz

# Create necessary directories and set permissions
RUN mkdir -p uploads logs cache sessions tmp /run/php \
    && chown -R www-data:www-data uploads/ logs/ cache/ sessions/ tmp/ phpmyadmin/ \
    && chmod -R 755 uploads/ logs/ cache/ sessions/ tmp/

# Copy and set up entrypoint script
COPY scripts/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
CMD ["/usr/local/bin/docker-entrypoint.sh"]