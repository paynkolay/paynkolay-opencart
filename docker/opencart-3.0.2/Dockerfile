FROM php:7.2-apache

# Debian buster is EOL — point apt at the archive
RUN sed -i -e 's|deb.debian.org/debian|archive.debian.org/debian|g' \
           -e 's|security.debian.org/debian-security|archive.debian.org/debian-security|g' \
           -e '/buster-updates/d' /etc/apt/sources.list \
    && echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid-until

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev unzip curl mariadb-client \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install gd mysqli pdo_mysql zip mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Download OpenCart 3.0.2.0
RUN curl -L https://github.com/opencart/opencart/archive/refs/tags/3.0.2.0.tar.gz -o /tmp/opencart.tar.gz \
    && tar -xzf /tmp/opencart.tar.gz -C /tmp \
    && cp -r /tmp/opencart-3.0.2.0/upload/* /var/www/html/ \
    && cp /var/www/html/config-dist.php /var/www/html/config.php \
    && cp /var/www/html/admin/config-dist.php /var/www/html/admin/config.php \
    && chown -R www-data:www-data /var/www/html \
    && rm -rf /tmp/opencart* /tmp/opencart.tar.gz

COPY docker/opencart-3.0.2/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
