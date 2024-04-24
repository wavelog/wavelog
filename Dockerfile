FROM php:8.3-apache

# Install dependencies
RUN set -ex; \
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
    rm -rf /var/lib/apt/lists/*;

# Enabling custom upload settings in PHP
RUN set -ex; \
    \
    { \
        echo 'file_uploads = On'; \
        echo 'memory_limit = 256M'; \
        echo 'upload_max_filesize = 64M'; \
        echo 'post_max_size = 64M'; \
        echo 'max_execution_time = 600'; \
    } > $PHP_INI_DIR/conf.d/wavelog.ini;

ENV CI_ENV=docker

COPY ./ /var/www/html/
WORKDIR /var/www/html

RUN mkdir ./userdata
RUN mkdir ./application/config/docker
RUN mv ./.htaccess.sample ./.htaccess
RUN echo "Setting www-data as owner of the html folder" \
&& chown -R www-data:www-data /var/www/html
RUN echo "Setting permissions to the install folder" \
&& cd /var/www/html \
&& chmod -R g+rw ./application/config/ \
&& chmod -R g+rw ./application/logs/ \
&& chmod -R g+rw ./assets/qslcard/ \
&& chmod -R g+rw ./backup/ \
&& chmod -R g+rw ./updates/ \
&& chmod -R g+rw ./uploads/ \
&& chmod -R g+rw ./userdata/ \
&& chmod -R g+rw ./images/eqsl_card_images/ \
&& chmod -R g+rw ./assets/ \
&& chmod -R g+rw ./application/config/docker/ \
&& chmod -R 777 /var/www/html/install
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
