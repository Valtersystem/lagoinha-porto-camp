FROM php:8.4-cli-alpine AS vendor

WORKDIR /var/www/html

RUN apk add --no-cache \
        git \
        unzip \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        oniguruma-dev \
    && docker-php-ext-install mbstring \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN mkdir -p \
        bootstrap/cache \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && composer install \
        --no-dev \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --optimize-autoloader

FROM node:22-alpine AS frontend

WORKDIR /var/www/html

COPY . .

RUN npm ci \
    && npm run build

FROM php:8.4-fpm-alpine AS php_base

WORKDIR /var/www/html

RUN apk add --no-cache \
        icu-data-full \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        oniguruma-dev \
    && docker-php-ext-install \
        mbstring \
        opcache \
        pdo_mysql \
    && apk del .build-deps

FROM php_base AS php_runtime

WORKDIR /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/lagoinha-porto-camp-entrypoint
RUN chmod +x /usr/local/bin/lagoinha-porto-camp-entrypoint

COPY . .
COPY --from=vendor /var/www/html/vendor /var/www/html/vendor
COPY --from=frontend /var/www/html/public/build /var/www/html/public/build

RUN rm -rf node_modules tests .git .github \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

USER www-data

ENTRYPOINT ["lagoinha-porto-camp-entrypoint"]
CMD ["php-fpm"]

FROM nginx:alpine AS web_runtime

WORKDIR /var/www/html

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=php_runtime /var/www/html /var/www/html

RUN mkdir -p /var/cache/nginx /var/run /var/log/nginx \
    && chown -R nginx:nginx /var/cache/nginx /var/run /var/log/nginx /var/www/html
