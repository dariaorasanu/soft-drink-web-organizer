FROM php:8.2-apache

# Instalează extensii PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Fix eroare "More than one MPM loaded"
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork

# Activează mod_rewrite pentru .htaccess
RUN a2enmod rewrite

# Copiază proiectul
COPY . /var/www/html/

# Permisiuni
RUN chown -R www-data:www-data /var/www/html

# Config Apache pentru AllowOverride
RUN echo '<Directory /var/www/html>\nAllowOverride All\nRequire all granted\n</Directory>' \
    > /etc/apache2/conf-available/project.conf \
    && a2enconf project

EXPOSE 80

# Rulează migrationurile apoi pornește Apache
CMD php /var/www/html/db/migrate.php migrate && apache2-foreground