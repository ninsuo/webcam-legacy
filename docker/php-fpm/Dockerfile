FROM php:7.0-fpm-alpine

COPY wait-for-it.sh /usr/bin/wait-for-it

RUN chmod +x /usr/bin/wait-for-it

RUN apk --update --no-cache add git g++ gcc libxslt-dev libmcrypt-dev icu-dev vim wget unzip \
    freetype libpng libjpeg-turbo freetype-dev libpng-dev libjpeg-turbo-dev oniguruma-dev bash

RUN docker-php-ext-install pdo
RUN docker-php-ext-install intl
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install xsl
RUN docker-php-ext-install ctype
RUN docker-php-ext-install gd
RUN docker-php-ext-install opcache

RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
        && pecl install redis \
        && docker-php-ext-enable redis.so

RUN cd /usr/local/etc/php/conf.d/ && \
  echo 'memory_limit = 1G' >> /usr/local/etc/php/conf.d/docker-php-ram-limit.ini

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php --install-dir=/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"

WORKDIR /var/www

CMD composer install ; wait-for-it mariadb:3306 -- bin/console doctrine:migrations:migrate ;  php-fpm

EXPOSE 9000
