FROM php:8.4-apache

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/tmp/composer

# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libpq-dev \
    git \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_mysql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier l'application entière pour que Laravel puisse exécuter package discovery
COPY . .

# Configurer Apache pour servir le répertoire public de Laravel
RUN a2enmod rewrite \
    && a2dissite 000-default || true

# Créer un fichier de configuration Apache for Laravel
RUN cat > /etc/apache2/sites-available/laravel.conf << 'EOF'
<VirtualHost *:80>
    DocumentRoot /var/www/html/public
    
    <Directory /var/www/html>
        AllowOverride All
        Require all granted
    </Directory>
    
    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
        RewriteEngine On
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ /index.php/$1 [L]
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/laravel-error.log
    CustomLog ${APACHE_LOG_DIR}/laravel-access.log combined
</VirtualHost>
EOF

RUN a2dissite 000-default || true && a2ensite laravel

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Créer le fichier .env depuis .env.example si nécessaire
RUN php -r "file_exists('.env') || copy('.env.example', '.env');"

# Générer la clé d'application
RUN php artisan key:generate

# Vérifier la configuration Apache
RUN apache2ctl configtest || (echo "Apache config test failed" && exit 1)

# Permissions - storage and bootstrap cache must be writable
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Exposer le port 80
EXPOSE 80

# Commande de démarrage
CMD ["apache2-foreground"]