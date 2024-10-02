FROM php:8.3-fpm
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libjpeg-dev \
        wkhtmltopdf \
        nano \
        zip \
        git \
    && docker-php-ext-install -j$(nproc) iconv \
    && apt-get install -y libicu-dev \
    && docker-php-ext-install intl \
    && docker-php-ext-configure intl

RUN docker-php-ext-configure gd --enable-gd --with-jpeg --with-webp --with-freetype
RUN docker-php-ext-install -j$(nproc) gd

RUN docker-php-ext-install pdo_mysql pdo mysqli

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN pecl channel-update pecl.php.net
RUN pecl install xdebug

RUN echo "xdebug.mode=debug\n" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        "xdebug.idekey=\"PHPSTORM\"\n" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        "xdebug.client_port=9001\n" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN docker-php-ext-enable xdebug

