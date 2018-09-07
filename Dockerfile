FROM php:7.1-apache
MAINTAINER "ere@labs.fi"

ARG MYSQL_ROOT_PASSWORD
ARG MYSQL_USER
ARG MYSQL_PASSWORD

WORKDIR /usr/local/mlinvoice
EXPOSE 80

RUN apt-get update && apt-get install -y --no-install-recommends \
        unzip \
        zlib1g-dev \
        libcurl4-openssl-dev \
        libmcrypt-dev \
        libxslt1-dev \
        mariadb-client && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j"$(nproc)" xsl intl mysqli mcrypt zip && \
    a2enmod rewrite && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /usr/local/mlinvoice/
COPY httpd_mlinvoice.conf.sample /etc/apache2/sites-available/000-default.conf
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --no-plugins --no-scripts && \
    chown -R www-data:www-data /usr/local/mlinvoice && \
    rm -rf /usr/local/mlinvoice/config.php && \
    sed -i -r "s/define\('_DB_SERVER_', '.*?'\);/define('_DB_SERVER_', 'mariadb');/" /usr/local/mlinvoice/config.php.sample && \
    sed -i -r "s/define\('_DB_USERNAME_', '.*?'\);/define('_DB_USERNAME_', '$MYSQL_USER');/" /usr/local/mlinvoice/config.php.sample && \
    sed -i -r "s/define\('_DB_PASSWORD_', '.*?'\);/define('_DB_PASSWORD_', '$MYSQL_PASSWORD');/" /usr/local/mlinvoice/config.php.sample

CMD ["apache2-foreground"]
