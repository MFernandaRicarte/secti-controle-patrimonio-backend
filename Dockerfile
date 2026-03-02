FROM php:8.2-apache

# Instalar dependências do sistema e extensões PHP
RUN apt-get update && apt-get install -y \
    libonig-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_mysql mbstring

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Configurar o DocumentRoot para api/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/api/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Permitir .htaccess (AllowOverride All) e configurar rewrite no VirtualHost
RUN sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Adicionar regras de rewrite diretamente na config do Apache
RUN echo '<Directory /var/www/html/api/public>\n\
    RewriteEngine On\n\
    RewriteCond %{REQUEST_FILENAME} !-f\n\
    RewriteCond %{REQUEST_FILENAME} !-d\n\
    RewriteRule ^(.*)$ index.php [QSA,L]\n\
</Directory>' >> /etc/apache2/conf-available/rewrite.conf \
    && a2enconf rewrite

# Copiar código do projeto
COPY . /var/www/html/

# Criar diretório de uploads com permissão
RUN mkdir -p /var/www/html/api/public/uploads && \
    chown -R www-data:www-data /var/www/html/api/public/uploads

# Expor porta 80
EXPOSE 80
