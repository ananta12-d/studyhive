FROM php:8.2-apache

# Install MySQL PDO extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy the studyhive folder into Apache web root
COPY studyhive/ /var/www/html/

# Create uploads folder with write permissions
RUN mkdir -p /var/www/html/uploads && chmod -R 777 /var/www/html/uploads

# Allow .htaccess overrides
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/studyhive.conf \
    && a2enconf studyhive

EXPOSE 80