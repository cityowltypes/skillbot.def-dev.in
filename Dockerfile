FROM ubuntu:20.04

ENV DEBIAN_FRONTEND=noninteractive

# upgrade system packages and configure timezone data
RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y tzdata apt-utils software-properties-common apt-transport-https build-essential && \
    ln -fs /usr/share/zoneinfo/Asia/Kolkata /etc/localtime && \
    dpkg-reconfigure tzdata && \
    # Add PHP 7.4 repository
    add-apt-repository ppa:ondrej/php && \
    apt-get update && \
    # install all packages
    apt-get install -y vim \
        zip \
        unzip \
        p7zip-full \
        curl \
        s3cmd \
        php7.4-fpm \
        php7.4-mysqli \
        php7.4-curl \
        php7.4-cli \
        php7.4-mbstring \
        php7.4-gd \
        php7.4-zip \
        php7.4-xml \
        php7.4-json \
        php7.4-bcmath \
        php7.4-intl \
        php7.4-soap \
        php7.4-xsl \
        poppler-utils \
        python3-pip \
        imagemagick \
        ffmpeg \
        net-tools \
        nginx \
        git \
        pdftk \
        nodejs npm \
        poppler-utils && \
    # setup composer for php (using specific version compatible with PHP 7.4)
    curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php && \
    php7.4 /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --version=2.2.18 && \
    # setup www-data's home directory
    usermod -d /tmp/www-data www-data

# set php configuration values
WORKDIR /var/www
ENV PHP_INI_DIR='/etc/php/7.4/fpm'
RUN sed -i 's/post_max_size = 8M/post_max_size = 1024M/' "${PHP_INI_DIR}/php.ini" && \
    sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 1024M/' "${PHP_INI_DIR}/php.ini" && \
    sed -i 's/memory_limit = 128M/memory_limit = 2048M/' "${PHP_INI_DIR}/php.ini" && \
    sed -i 's/max_execution_time = 30/max_execution_time = 300/' "${PHP_INI_DIR}/php.ini" && \
    sed -i 's/max_input_time = 60/max_input_time = 300/' "${PHP_INI_DIR}/php.ini" && \
    sed -i "\|include /etc/nginx/sites-enabled/\*;|d" "/etc/nginx/nginx.conf"

COPY . .

# Set PHP path for npm and composer commands
ENV PATH="/usr/bin:$PATH"
RUN update-alternatives --install /usr/bin/php php /usr/bin/php7.4 1 && \
    npm i && \
    composer install --no-dev --optimize-autoloader && \
    ## phpmyadmin (using version compatible with PHP 7.4)
    curl https://files.phpmyadmin.net/phpMyAdmin/5.1.4/phpMyAdmin-5.1.4-all-languages.tar.gz -o pma.tar.gz && \
    mkdir /var/www/phpmyadmin && \
    tar -xzf pma.tar.gz -C /var/www/phpmyadmin --strip-components=1 && \
    rm pma.tar.gz

RUN mkdir -p uploads logs && \
    chown -R www-data: uploads/ logs/ && \
    service php7.4-fpm restart

EXPOSE 80

COPY scripts/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["/usr/local/bin/docker-entrypoint.sh"]