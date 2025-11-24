FROM httpd:2.4.65-alpine

LABEL maintainer="a.khalifi@sapalomera.cat"
LABEL description="Apache HTTPD amb SSL, vhosts, logs JSON i PHP via CGI si cal"

# OpenSSL per generar certificats
RUN apk add --no-cache openssl

# Crear carpeta de certificats
RUN mkdir -p /usr/local/apache2/certs

# Certificat autosignat (per frontend.local i api.local)
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /usr/local/apache2/certs/server.key \
    -out /usr/local/apache2/certs/server.crt \
    -subj "/C=ES/ST=Catalunya/L=Girona/O=ASIX/OU=Dev/CN=frontend.local"

# Copiar configuració i llocs
COPY conf/httpd.conf /usr/local/apache2/conf/httpd.conf
COPY conf/vhosts/ /usr/local/apache2/conf/vhosts/
COPY sites/ /usr/local/apache2/sites/

EXPOSE 80 443

CMD ["httpd-foreground"]
