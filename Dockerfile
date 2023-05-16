# Образ php + fpm + alpine из внешнего репозитория
FROM php:7.4.23-fpm-alpine3.13 as base

# Задаем расположение рабочей директории
ENV WORK_DIR /var/www/application

RUN set -xe \
    && docker-php-ext-install -j$(nproc) pdo \
    && docker-php-ext-install -j$(nproc) pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

FROM base

# Указываем, что текущая папка проекта копируется в рабочую дирректорию контейнера https://docs.docker.com/engine/reference/builder/#copy
COPY . ${WORK_DIR}

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]