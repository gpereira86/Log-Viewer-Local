FROM php:8.2-apache

# Instala dependências para extensão ssh2
RUN apt-get update && apt-get install -y \
    libssh2-1-dev \
    && rm -rf /var/lib/apt/lists/*

# Instala extensões PHP
RUN docker-php-ext-install opcache

# Instala extensão ssh2 via PECL
RUN pecl install ssh2-1.4 \
    && docker-php-ext-enable ssh2

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf

# Permitir que o .htaccess controle as rotas (front controller)
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

WORKDIR /var/www/html

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80


