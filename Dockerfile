FROM php:8.2-apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev fonts-dejavu-core \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql mbstring gd \
    && rm -rf /var/lib/apt/lists/*
RUN a2enmod rewrite
COPY apache-rewrite.conf /etc/apache2/conf-available/nepal-disaster-archive-rewrite.conf
RUN a2enconf nepal-disaster-archive-rewrite
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html