# Use the official PHP Apache image
FROM php:8.2-apache

# Install system dependencies including PostgreSQL client
RUN apt-get update && apt-get install -y \
    zip unzip git curl \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev \
    libpq-dev

# Enable required PHP extensions
# ✅ Added pdo_pgsql and pgsql for Supabase PostgreSQL support
# ✅ Kept MySQL extensions for backward compatibility
RUN docker-php-ext-install \
    mysqli pdo pdo_mysql \
    pdo_pgsql pgsql \
    mbstring exif pcntl bcmath gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy project files to the Apache root
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Change Apache document root (important for Render)
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Update Apache configuration to use the new root
RUN sed -ri -e 's!/var/www/html!/var/www/html!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Expose Render port (Render expects port 10000)
EXPOSE 10000

# Set Apache to listen on Render's required port
RUN sed -i 's/80/10000/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Start Apache
CMD ["apache2-foreground"]
