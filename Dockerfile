FROM php:8.3-apache
ENV CI_ENV=docker

# Install dependencies
RUN set -e; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        cron \
    ; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    \
    docker-php-ext-install -j "$(nproc)" \
        mysqli \
        zip \
        gd \
    ; \
    \
    a2enmod rewrite; \
    \
    apt-get clean;

# Enabling custom upload settings in PHP
RUN set -e; \
    \
    { \
        echo 'file_uploads = On'; \
        echo 'memory_limit = 256M'; \
        echo 'upload_max_filesize = 64M'; \
        echo 'post_max_size = 64M'; \
        echo 'max_execution_time = 600'; \
    } > $PHP_INI_DIR/conf.d/wavelog.ini;

# Copy proper file to target image
COPY ./ /var/www/html/
WORKDIR /var/www/html

# Setting permissions as: https://github.com/wavelog/Wavelog/wiki/Installation
RUN set -e; \
    \
    mkdir ./application/config/docker; \
    \
    mv ./htaccess.sample ./.htaccess; \
    sed -i "s/\$config\['index_page'\] = 'index.php';/\$config\['index_page'\] = '';/g" ./install/config/config.php; \
    \
    chown -R root:www-data /var/www/html; \
    \
    chmod -R g+rw ./application/cache/; \
    chmod -R g+rw ./application/config/; \
    chmod -R g+rw ./application/logs/; \
    chmod -R g+rw ./assets/; \
    chmod -R g+rw ./backup/; \
    chmod -R g+rw ./updates/; \
    chmod -R g+rw ./uploads/; \
    chmod -R g+rw ./userdata/; \
    chmod -R g+rw ./images/eqsl_card_images/; \
    chmod -R g+rw ./install/;

# Create the cron job
RUN set -e; \
    \
    touch /etc/cron.d/wavelog; \
    echo "* * * * * curl --silent http://localhost/index.php/cron/run &>/dev/null" >> /etc/cron.d/wavelog; \
    \
    chmod 0644 /etc/cron.d/wavelog;\
    \
    crontab /etc/cron.d/wavelog;\
    \
    mkdir -p /var/log/cron; \
    \
    sed -i 's/^exec /service cron start\n\nexec /' /usr/local/bin/apache2-foreground;
