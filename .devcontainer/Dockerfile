# Use the official PHP image with Apache server
FROM php:8.1-apache

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache modules if needed (like mod_rewrite)
RUN a2enmod rewrite

# Install additional tools (e.g., git, unzip) if needed
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    vim

# Install Composer (PHP package manager)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /var/www/html

# Copy the current directory contents into the container at /var/www/html
COPY . /var/www/html

# Ensure the script is served by Apache
CMD ["apache2-foreground"]
