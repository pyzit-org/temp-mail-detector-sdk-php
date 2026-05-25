FROM php:8.3-cli-alpine

RUN apk add --no-cache curl git unzip bash curl-dev oniguruma-dev

RUN docker-php-ext-install curl mbstring

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json ./
RUN composer install --no-interaction --prefer-dist --no-scripts

COPY . .

CMD ["vendor/bin/phpunit", "--testdox"]