# syntax=docker/dockerfile:1

FROM php:8.5-apache

# Install dependencies and PHP extensions
RUN apt-get update && \
    apt-get install -y git unzip libzip-dev default-mysql-client ghostscript && \
    docker-php-ext-install pdo pdo_mysql zip && \
    a2enmod rewrite && \
    rm -rf /var/lib/apt/lists/*

# Copy Composer from the official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy your application code
COPY ./src /var/www/html

# Update Apache's DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf
RUN sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy the entrypoint script into the container
COPY ./docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Make sure it’s executable
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Create writable uploads directory for chart PDFs
RUN mkdir -p /var/www/html/public/uploads/charts \
    && chown -R www-data:www-data /var/www/html/public/uploads

# Expose Apache’s default port
EXPOSE 80

# ENTRYPOINT and CMD go at the bottom:
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]