FROM php:8.2-apache
   RUN docker-php-ext-install pdo pdo_mysql
   COPY studyhive/ /var/www/html/
   RUN mkdir -p /var/www/html/uploads && chmod 777 /var/www/html/uploads