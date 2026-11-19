FROM php:8.2-apache

# Install ekstensi PHP untuk koneksi MySQL (PDO)
RUN docker-php-ext-install pdo pdo_mysql

# Aktifkan mod_rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html