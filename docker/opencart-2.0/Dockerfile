FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libonig-dev unzip curl mariadb-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli pdo_mysql zip mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Download OpenCart 2.0.3.1
RUN curl -L https://github.com/opencart/opencart/archive/refs/tags/2.0.3.1.tar.gz -o /tmp/opencart.tar.gz \
    && tar -xzf /tmp/opencart.tar.gz -C /tmp \
    && cp -r /tmp/opencart-2.0.3.1/upload/* /var/www/html/ \
    && cp /var/www/html/config-dist.php /var/www/html/config.php \
    && cp /var/www/html/admin/config-dist.php /var/www/html/admin/config.php \
    && chown -R www-data:www-data /var/www/html \
    && rm -rf /tmp/opencart* /tmp/opencart.tar.gz

COPY docker/opencart-2.0/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
