FROM httpd:2.4.65-alpine

# Instal·lar PHP i extencions
RUN apk update && apk add --no-cache \
    php82 \
    php82-apache2 \
    php82-mysqli \
    php82-json \
    php82-openssl \
    php82-curl \
    php82-zlib \
    php82-session \
    php82-pecl-redis \
    openssl

# Crear la carpeta de certs
RUN mkdir -p /usr/local/apache2/conf/certs

# Generar certificats
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /usr/local/apache2/conf/certs/server.key \
    -out /usr/local/apache2/conf/certs/server.crt \
    -subj "/C=ES/ST=Barcelona/L=Barcelona/O=MyOrg/OU=IT/CN=frontend.local"