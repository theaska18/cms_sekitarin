FROM php:8.2-apache

# CI3 uses mysqli by default; enable Apache overrides for the framework .htaccess files.
RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install mysqli pdo_mysql \
    && a2enmod rewrite \
    && printf '%s\n' \
        '<Directory /var/www/html>' \
        '    AllowOverride All' \
        '    Require all granted' \
        '</Directory>' \
        > /etc/apache2/conf-available/codeigniter.conf \
    && a2enconf codeigniter

COPY ./web/ /var/www/html/
COPY ./docker/backup/ /usr/local/bin/cms-backup/
COPY ./docker/cms-entrypoint.sh /usr/local/bin/cms-entrypoint.sh

# CI3 writes runtime logs/cache here; Compose mounts these paths as named volumes.
RUN chmod +x /usr/local/bin/cms-entrypoint.sh /usr/local/bin/cms-backup/*.sh \
    && chown -R www-data:www-data /var/www/html/application/cache /var/www/html/application/logs

# Run Apache in the foreground while the backup scheduler runs in background.
ENTRYPOINT ["/usr/local/bin/cms-entrypoint.sh"]
CMD ["apache2-foreground"]
