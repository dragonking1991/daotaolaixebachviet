FROM php:7.4-apache

# Enable Apache modules
RUN a2enmod rewrite headers expires deflate

# Install PHP extensions + MariaDB server in one layer
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    unzip \
    mariadb-server \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        curl \
        zip \
        intl \
        mbstring \
        exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure Apache to allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Set PHP config
RUN echo "upload_max_filesize = 64M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "date.timezone = Asia/Ho_Chi_Minh" >> /usr/local/etc/php/conf.d/uploads.ini

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Configure Apache: listen on port 8080 (Fly.io internal)
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Initialize MariaDB data directory
RUN mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld \
    && mysql_install_db --user=mysql --datadir=/var/lib/mysql 2>/dev/null

# Make startup script executable
RUN chmod +x /var/www/html/start.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/upload

EXPOSE 8080
CMD ["/var/www/html/start.sh"]
