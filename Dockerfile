FROM php:8.2-apache

# Instalar dependências do sistema e extensões PHP
RUN apt-get update && apt-get install -y \
    libonig-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_mysql mbstring

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Copiar configuração do Apache (VirtualHost com rewrite)
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Copiar código do projeto
COPY . /var/www/html/

# Criar diretório de uploads com permissão
RUN mkdir -p /var/www/html/api/public/uploads && \
    chown -R www-data:www-data /var/www/html/api/public/uploads

# Expor porta 80
EXPOSE 80
