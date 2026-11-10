FROM php:8.2-apache

# CI3 uses mysqli by default; enable Apache overrides for the framework .htaccess files.
RUN docker-php-ext-install mysqli pdo_mysql \
    && a2enmod rewrite \
    && printf '%s\n' \
        '<Directory /var/www/html>' \
        '    AllowOverride All' \
        '    Require all granted' \
        '</Directory>' \
        > /etc/apache2/conf-available/codeigniter.conf \
    && a2enconf codeigniter

COPY ./web/ /var/www/html/

# CI3 writes runtime logs/cache here; Compose mounts these paths as named volumes.
RUN chown -R www-data:www-data /var/www/html/application/cache /var/www/html/application/logs
