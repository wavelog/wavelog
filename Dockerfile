FROM php:8.3-apache
ENV CI_ENV=docker

COPY --from=ghcr.io/mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install dependencies
RUN apt-get update && apt-get install -y --no-install-recommends cron
RUN install-php-extensions \
        mysqli \
        zip \
        gd; \
    \
    a2enmod rewrite; \
    apt-get clean

# Enabling custom upload settings in PHP
RUN printf "file_uploads = On\n\
memory_limit = 256M\n\
upload_max_filesize = 64M\n\
post_max_size = 64M\n\
max_execution_time = 600\n" > $PHP_INI_DIR/conf.d/wavelog.ini

# Copy proper file to target image
COPY ./ /var/www/html/
WORKDIR /var/www/html

# Setting permissions as: https://github.com/wavelog/Wavelog/wiki/Installation
RUN mkdir ./application/config/docker; \
    mv ./htaccess.sample ./.htaccess; \
    sed -i "s/\$config\['index_page'\] = 'index.php';/\$config\['index_page'\] = '';/g" ./install/config/config.php; \
    chown -R root:www-data /var/www/html; \
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
RUN touch /etc/cron.d/wavelog; \
    echo "* * * * * curl --silent http://localhost/index.php/cron/run &>/dev/null" >> /etc/cron.d/wavelog; \
    chmod 0644 /etc/cron.d/wavelog;\
    crontab /etc/cron.d/wavelog;\
    mkdir -p /var/log/cron; \
    sed -i 's/^exec /service cron start\n\nexec /' /usr/local/bin/apache2-foreground;
