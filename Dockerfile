# Zoloverse — PHP + Apache image for Render
FROM php:8.3-apache

# PostgreSQL driver (Render DB is Postgres), MySQL driver (local parity), curl (Google OAuth)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libcurl4-openssl-dev \
    && docker-php-ext-install pdo_pgsql pgsql pdo_mysql curl \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy the application into the Apache document root
WORKDIR /var/www/html
COPY . /var/www/html/

# Render injects the external port via $PORT — Apache must listen on it
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]