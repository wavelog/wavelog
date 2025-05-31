
FROM php:8.3-apache
ENV CI_ENV=docker

# Copy php-extension-installer
COPY --from=ghcr.io/mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install dependencies
RUN apt-get update && apt-get install -y --no-install-recommends cron && \
    install-php-extensions \
        mysqli \
        zip \
        gd && \
    a2enmod rewrite && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Custom PHP upload settings
RUN printf "file_uploads = On\n\
memory_limit = 256M\n\
upload_max_filesize = 64M\n\
post_max_size = 64M\n\
max_execution_time = 600\n" > $PHP_INI_DIR/conf.d/wavelog.ini

# Set UID:GID using ARG. Set either in docker-compose.yml or docker-compose.override.yml
ARG APP_UID=1003
ARG APP_GID=1004

# Create User and Group with host-matching UID:GID
RUN groupadd -g ${APP_GID} appgroup && \
    useradd -u ${APP_UID} -g appgroup -m appuser

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Set permissions and update config
RUN mkdir ./application/config/docker && \
    mv ./htaccess.sample ./.htaccess && \
    sed -i 's/APACHE_RUN_USER=www-data/APACHE_RUN_USER=appuser/' /etc/apache2/envvars && \
    sed -i 's/APACHE_RUN_GROUP=www-data/APACHE_RUN_GROUP=appgroup/' /etc/apache2/envvars && \
    sed -i "s/\$config\['index_page'\] = 'index.php';/\$config\['index_page'\] = '';/g" ./install/config/config.php && \
    chown -R ${APP_UID}:${APP_GID} /var/www/html && \
    chmod -R g+rw ./application/cache/ && \
    chmod -R g+rw ./application/config/ && \
    chmod -R g+rw ./application/logs/ && \
    chmod -R g+rw ./assets/ && \
    chmod -R g+rw ./backup/ && \
    chmod -R g+rw ./updates/ && \
    chmod -R g+rw ./uploads/ && \
    chmod -R g+rw ./userdata/ && \
    chmod -R g+rw ./images/eqsl_card_images/ && \
    chmod -R g+rw ./install/

# Add proper Directory config for Apache
RUN echo "<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" > /etc/apache2/conf-available/wavelog.conf && \
    a2enconf wavelog

# Create the cron job
RUN touch /etc/cron.d/wavelog && \
    echo "* * * * * curl --silent http://localhost/index.php/cron/run &>/dev/null" >> /etc/cron.d/wavelog && \
    chmod 0644 /etc/cron.d/wavelog && \
    crontab /etc/cron.d/wavelog && \
    mkdir -p /var/log/cron && \
    sed -i 's/^exec /service cron start\n\nexec /' /usr/local/bin/apache2-foreground

# Switch to created user
USER ${APP_UID}

# Expose HTTP
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
