FROM httpd:2.4.65-alpine

# Install PHP and extensions
# We use php82. Adjust version if needed based on Alpine version in the base image.
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

# Create certs directory
RUN mkdir -p /usr/local/apache2/conf/certs

# Generate self-signed certificate
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /usr/local/apache2/conf/certs/server.key \
    -out /usr/local/apache2/conf/certs/server.crt \
    -subj "/C=ES/ST=Barcelona/L=Barcelona/O=MyOrg/OU=IT/CN=frontend.local"
