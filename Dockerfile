FROM php:8.2-apache

# Habilitar extensões PHP necessárias
RUN docker-php-ext-install pdo pdo_mysql mbstring

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Configurar o DocumentRoot para api/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/api/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Permitir .htaccess (AllowOverride All)
RUN sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copiar código do projeto
COPY . /var/www/html/

# Criar diretório de uploads com permissão
RUN mkdir -p /var/www/html/api/public/uploads && \
    chown -R www-data:www-data /var/www/html/api/public/uploads

# Expor porta 80
EXPOSE 80
