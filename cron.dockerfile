FROM php:7.4-fpm-alpine3.13

RUN docker-php-ext-install pdo pdo_mysql

RUN apk update && apk add --no-cache supervisor

COPY . /var/www

COPY crontab /etc/crontabs/root

CMD ["/usr/bin/supervisord"]

CMD ["crond", "-f"]