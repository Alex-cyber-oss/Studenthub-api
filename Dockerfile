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
    curl \
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
RUN cp /var/www/html/docker/apache.conf /etc/apache2/sites-available/laravel.conf \
    && a2enmod rewrite \
    && a2dissite 000-default \
    && a2ensite laravel

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Créer le fichier .env pour production
RUN cat > .env << 'EOF'
APP_NAME=StudentHub
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=studenthub
DB_USERNAME=postgres
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=cookie
QUEUE_CONNECTION=sync

# EXPLICITEMENT désactiver tout serveur de développement
SERVER_PORT=80
LARAVEL_SAIL=false
ARTISAN_SERVE=false
PHP_CLI_SERVER_WORKERS=0

# Forcer l'utilisation d'Apache uniquement
WEB_SERVER=apache
EOF

# Générer a clé d'application
RUN php artisan key:generate --force

# Clear and cache all Laravel configurations for production
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

# S'assurer qu'aucun processus de développement ne démarre
RUN echo "Production mode - No auto-serve" > /tmp/production-mode.txt

# Ensure NO development processes run
RUN rm -f storage/logs/* || true

# Vérifier la configuration Apache
RUN apache2ctl configtest || (echo "Apache config test failed" && exit 1)

# Permissions - storage and bootstrap cache must be writable
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Exposer le port 80
EXPOSE 80

# Health check (utilise le port injecté par Railway)
HEALTHCHECK --interval=10s --timeout=5s --start-period=20s --retries=6 \
    CMD sh -c 'curl -fsS "http://localhost:${PORT:-80}/api/test" || exit 1'

# Démarrage Apache sur le port dynamique Railway
CMD ["sh", "-c", "PORT_TO_USE=${PORT:-80}; sed -i \"s/Listen 80/Listen ${PORT_TO_USE}/\" /etc/apache2/ports.conf; sed -i \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT_TO_USE}>/\" /etc/apache2/sites-available/laravel.conf; apache2-foreground"]