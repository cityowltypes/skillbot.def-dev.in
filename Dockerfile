FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

# upgrade system packages and configure timezone data
RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y tzdata apt-utils software-properties-common apt-transport-https build-essential && \
    ln -fs /usr/share/zoneinfo/Asia/Kolkata /etc/localtime && \
    dpkg-reconfigure tzdata && \
    # install all packages
    apt-get install -y vim \
        zip \
        unzip \
        p7zip-full \
        curl \
        s3cmd \
        php8.3-fpm \
        php8.3-mysqli \
        php8.3-curl \
        php8.3-cli \
        php8.3-mbstring \
        php8.3-gd \
        php8.3-zip \
        php8.3-xml \
        poppler-utils \
        python3-pip \
        imagemagick \
        ffmpeg \
        net-tools \
        nginx \
        git \
        pdftk \
        poppler-utils && \
    # setup composer for php
    curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php && \
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    # setup www-data's home directory
    usermod -d /tmp/www-data www-data

# set php configuration values
WORKDIR /var/www
ENV PHP_INI_DIR='/etc/php/8.3/fpm'
RUN sed -i 's/post_max_size = 8M/post_max_size = 1024M/' "${PHP_INI_DIR}/php.ini" && \
    sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 1024M/' "${PHP_INI_DIR}/php.ini" && \
    sed -i 's/memory_limit = 128M/memory_limit = 2048M/' "${PHP_INI_DIR}/php.ini" && \
    sed -i "\|include /etc/nginx/sites-enabled/\*;|d" "/etc/nginx/nginx.conf"

## setup tribe
RUN curl -L -o tribe.tar.gz https://github.com/tribe-framework/tribe/archive/refs/tags/v3.1.9.tar.gz && \
    tar -xzf tribe.tar.gz -C /var/www --strip-components=1 && \
    rm tribe.tar.gz

COPY composer.json ./
COPY composer.lock ./

RUN composer i && \
    ## phpmyadmin
    curl https://files.phpmyadmin.net/phpMyAdmin/5.2.1/phpMyAdmin-5.2.1-all-languages.tar.gz -o pma.tar.gz && \
    mkdir /var/www/phpmyadmin && \
    tar -xzf pma.tar.gz -C /var/www/phpmyadmin --strip-components=1 && \
    rm pma.tar.gz

## junction
COPY "applications/junction" "junction/dist"

RUN chown -R www-data: uploads/ logs/ && \
    service php8.3-fpm restart;

EXPOSE 80
EXPOSE 81

COPY scripts/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["/usr/local/bin/docker-entrypoint.sh"]
