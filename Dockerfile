FROM php:8.4-apache

# we always need this env var, otherwise wavelog can't work properly in a docker environment
ENV CI_ENV=docker

# prepare the php install helper
COPY --from=ghcr.io/mlocati/php-extension-installer:2.11.6 /usr/bin/install-php-extensions /usr/local/bin/

# system packages
RUN apt-get update \
    && apt-get install -y --no-install-recommends cron \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# php extensions
RUN install-php-extensions \
        mysqli \
        zip \
        redis \
        memcached \
        apcu \
        gd

# server config
RUN a2enmod rewrite \
    && echo "* * * * * root curl --silent http://localhost/index.php/cron/run >/dev/null 2>&1" > /etc/cron.d/wavelog \
    && chmod 0644 /etc/cron.d/wavelog

# application
WORKDIR /var/www/html
COPY --chown=root:www-data ./ ./

# basic setup steps and permissions
RUN mkdir -p ./application/config/docker \
    && chown root:www-data ./application/config/docker \
    && mv ./htaccess.sample ./.htaccess \
    && sed -i "s/\$config\['index_page'\] = 'index.php';/\$config\['index_page'\] = '';/g" ./install/config/config.php \
    && while read -r dir || [ -n "$dir" ]; do \
        case "$dir" in ''|'#'*) continue ;; esac; \
        chmod -R g+rw "./$dir"; \
    done < ./docker/writable-dirs

# Entrypoint
RUN mv ./docker/entrypoint.sh /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]