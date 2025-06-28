FROM dunglas/frankenphp

# Be sure to replace "your-domain-name.example.com" by your domain name
ENV SERVER_NAME=infynia.vizzo.digital
# If you want to disable HTTPS, use this value instead:
#ENV SERVER_NAME=:80

# Enable PHP production settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Install MySQL PDO extension
RUN docker-php-ext-install pdo pdo_mysql

# Add Node.js + npm (for Vite)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

# Optional: install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy the PHP files of your project in the public directory
COPY . /app