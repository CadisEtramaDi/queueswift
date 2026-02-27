# Use the official PHP image with Apache web server
FROM php:8.2-apache

# Install the mysqli extension for database connections
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite (useful if your API relies on URL routing)
RUN a2enmod rewrite

# Copy your project files into the default Apache directory
COPY . /var/www/html/

# Set the correct permissions
RUN chown -R www-data:www-data /var/www/html/