FROM php:8.3-apache

# mod_rewrite: necessário para o front controller (todas as rotas caem no public/index.php).
RUN a2enmod rewrite

# Driver do PostgreSQL para o PDO.
# libpq-dev traz os headers do cliente Postgres que o pdo_pgsql precisa para compilar.
RUN apt-get update \
    && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Aponta o DocumentRoot do Apache para a pasta public/.
COPY docker/apache.conf /etc/apache2/sites-enabled/000-default.conf
