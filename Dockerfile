FROM php:8.3-apache
ENV CI_ENV=docker

# Install dependencies
RUN set -e; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libzip-dev \
        cron \
    ; \
    \
    docker-php-ext-install -j "$(nproc)" \
        mysqli \
        zip \
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
    mkdir ./userdata; \
    mkdir ./application/config/docker; \
    \
    mv ./.htaccess.sample ./.htaccess; \
    sed -i "s/\$config\['index_page'\] = 'index.php';/\$config\['index_page'\] = '';/g" ./install/config/config.php; \
    \
    chown -R root:www-data /var/www/html; \
    \
    chmod -R g+rw ./application/config/; \
    chmod -R g+rw ./application/logs/; \
    chmod -R g+rw ./assets/; \
    chmod -R g+rw ./backup/; \
    chmod -R g+rw ./updates/; \
    chmod -R g+rw ./uploads/; \
    chmod -R g+rw ./userdata/; \
    chmod -R g+rw ./images/eqsl_card_images/; \
    chmod -R g+rw ./install/;

RUN echo "Installing cronjobs" \
RUN touch /etc/crontab && \
    echo "0 */12 * * * curl --silent http://localhost/clublog/upload &>/dev/null" >> /etc/crontab && \
    echo "10 */12 * * * curl --silent http://localhost/eqsl/sync &>/dev/null" >> /etc/crontab && \
    echo "20 */12 * * * curl --silent http://localhost/qrz/upload &>/dev/null" >> /etc/crontab && \
    echo "30 */12 * * * curl --silent http://localhost/qrz/download &>/dev/null" >> /etc/crontab && \
    echo "40 */12 * * * curl --silent http://localhost/hrdlog/upload &>/dev/null" >> /etc/crontab && \
    echo "0 1 * * * curl --silent http://localhost/lotw/lotw_upload &>/dev/null" >> /etc/crontab && \
    echo "10 1 * * * curl --silent http://localhost/update/lotw_users &>/dev/null" >> /etc/crontab && \
    echo "20 1 * * 1 curl --silent http://localhost/update/update_clublog_scp &>/dev/null" >> /etc/crontab && \
    echo "0 2 1 */1 * curl --silent http://localhost/update/update_sota &>/dev/null" >> /etc/crontab && \
    echo "10 2 1 */1 * curl --silent http://localhost/update/update_wwff &>/dev/null" >> /etc/crontab && \
    echo "20 2 1 */1 * curl --silent http://localhost/update/update_pota &>/dev/null" >> /etc/crontab && \
    echo "0 3 1 */1 *  curl --silent http://localhost/update/update_dok &>/dev/null" >> /etc/crontab
RUN chmod 0644 /etc/crontab
RUN crontab </etc/crontab
RUN mkdir -p /var/log/cron
RUN sed -i 's/^exec /service cron start\n\nexec /' /usr/local/bin/apache2-foreground
