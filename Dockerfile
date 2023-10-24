FROM php:7.4-fpm-alpine3.13
WORKDIR /var/www

RUN apk update && apk add \
    build-base \
    freetype-dev \
    libzip-dev \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl

    RUN docker-php-ext-install  pdo_mysql zip exif pcntl
#    RUN docker-php-ext-configure gd --with-gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include/
    RUN docker-php-ext-install gd

    #copy config
    COPY ./config/php/local.ini /usr/local/etc/php/config.d/local.ini

    RUN addgroup -g 1000 -S www && \
        adduser -u 1000 -S www -G www

    USER www

    COPY --chown=www:www . /var/www
    
    RUN chown -R www:www /var/www/storage
    RUN chmod -R ug+w /var/www/storage

    EXPOSE 9000
    CMD ["php-fpm"]