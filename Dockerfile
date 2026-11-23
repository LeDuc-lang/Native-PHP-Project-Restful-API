FROM php:8.3-cli

RUN docker-php-ext-install pdo_mysql

WORKDIR /app

COPY . /app

RUN mkdir -p /app/logs && chmod 775 /app/logs

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/router.php"]
