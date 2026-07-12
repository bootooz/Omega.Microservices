# 1. Используем официальный образ PHP с расширениями для работы Laravel
FROM php:8.3-fpm AS builder

# 2. Устанавливаем системные зависимости и PHP-расширения
RUN apt-get update \
    && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev zip git unzip curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
        && apt-get install -y nodejs

# 3. Устанавливаем Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 4. Устанавливаем рабочую директорию
WORKDIR /var/www/html

# 1. Копируем только файлы зависимостей
COPY ../laravel/composer.json ./laravel/composer.lock ./laravel/package.json ./laravel/package-lock.json ./

# 2. Ставим зависимости без autoloader-scripts (чтобы избежать ошибок artisan)
#RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist

RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction --prefer-dist

RUN npm i

# 3. Копируем остальные исходники (тут появится artisan)
COPY ../laravel .

RUN npx update-browserslist-db@latest

RUN npm run build

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 4. Теперь можно выполнить post-scripts
RUN composer run-script post-autoload-dump || true

RUN php artisan key:generate \
    && php artisan config:clear && php artisan cache:clear && php artisan config:cache && php artisan config:cache && php artisan route:cache && php artisan view:cache


############################
# Stage 2: php-fpm (app)   #
############################
FROM php:8.3-fpm AS app

# 2. Устанавливаем системные зависимости и PHP-расширения
RUN apt-get update \
    && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev zip git unzip curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

WORKDIR /var/www/html

COPY --from=builder /var/www/html ./

RUN php artisan config:clear && php artisan cache:clear && php artisan config:cache && php artisan config:cache && php artisan route:cache && php artisan view:cache

# 10. Запускаем php-fpm (или другую основную команду)
#CMD ["php-fpm"] # эта команда перенесена в depends-on и запускается после запуска db

############################
# Stage 3: nginx (frontend)#
############################
FROM nginx:alpine AS nginx

WORKDIR /var/www/html

# Копируем только собранную статику и index.php
COPY --from=builder /var/www/html/public /var/www/html/public

# Копируем nginx конфиг
COPY docker/nginx/conf.d/my.conf /etc/nginx/conf.d/default.conf
