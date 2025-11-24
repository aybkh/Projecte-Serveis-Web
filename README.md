# Projecte Final – Stack Docker (Apache + MySQL + Redis + API)

## 📌 Descripció General

Aquest projecte implementa un **stack complet d’integració** utilitzant **Docker Compose**, format per:

- **Apache** (amb HTTPS, Virtual Hosts, mod_rewrite, logs JSON i capçaleres segures)
- **MySQL 8.0.35** (base de dades persistent + inicialització)
- **Redis 7** (cache + comptador de visites)
- **phpMyAdmin** (administració web)
- **Frontend** (`frontend.local`)
- **API REST** (`api.local`)

Tot el sistema està separat en **dues xarxes** (frontend/backend), utilitza **volums persistents**, fitxer **.env** amb credencials, i implementa un **flux real d’aplicació** amb comunicació entre contenidors.

---

# 🚀 Arquitectura del sistema

```text
                          ┌──────────────────┐
                          │  phpMyAdmin      │
                          │   (8081)         │
                          └───────▲──────────┘
                                  │
                 backend-network  │
                                  │
 ┌────────────┐    backend   ┌─────────────┐
 │   Redis    │◀────────────▶│   MySQL     │
 │ (cache)    │              │ (database)  │
 └─────▲──────┘              └──────▲──────┘
       │                            │
       │ backend-network            │
       ▼                            │
 ┌──────────────────────────────────────────┐
 │                  Apache                  │
 │            HTTPS + VHOSTS               │
 │  frontend.local | api.local             │
 └─────────────────┬────────────────────────┘
                   │
                   │ frontend-network
                   ▼
         Navegador (client)

```

---

# 📁 Estructura del projecte

```text
projecte-final/
├── docker-compose.yml
├── .env
├── .gitignore
├── README.md
│
├── apache/
│   ├── Dockerfile
│   ├── conf/
│   │   ├── httpd.conf
│   │   └── vhosts/
│   │        ├── frontend.conf
│   │        └── api.conf
│   └── sites/
│       ├── frontend/
│       │   ├── index.php
│       │   └── .htaccess
│       └── api/
│           ├── index.php
│           └── .htaccess
│
├── mysql/
│   └── init/
│       └── 01-schema.sql
│
└── logs/
```

---

# ⚙️ Fitxer `.env`

```env
MYSQL_ROOT_PASSWORD=supersecret
MYSQL_DATABASE=appdb
MYSQL_USER=appuser
MYSQL_PASSWORD=apppassword

PHPMYADMIN_PORT=8081
FRONTEND_PORT=8080
API_PORT=8082
TZ=Europe/Madrid
```

---

# 🐳 Fitxer `docker-compose.yml`

```yaml
version: "3.9"

services:
  apache:
    build:
      context: ./apache
      dockerfile: Dockerfile
    container_name: pf-apache
    restart: unless-stopped
    ports:
      - "${FRONTEND_PORT}:80"
      - "8443:443"
    volumes:
      - ./apache/conf:/usr/local/apache2/conf
      - ./apache/sites:/usr/local/apache2/sites
      - ./logs/apache:/usr/local/apache2/logs
    environment:
      - TZ=${TZ}
    depends_on:
      - mysql
      - redis
    networks:
      - frontend-network
      - backend-network

  mysql:
    image: mysql:8.0.35
    container_name: pf-mysql
    restart: unless-stopped
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=${MYSQL_DATABASE}
      - MYSQL_USER=${MYSQL_USER}
      - MYSQL_PASSWORD=${MYSQL_PASSWORD}
    volumes:
      - mysql-data:/var/lib/mysql
      - ./mysql/init:/docker-entrypoint-initdb.d
    ports:
      - "3306:3306"
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 30s
    networks:
      - backend-network

  redis:
    image: redis:7-alpine
    container_name: pf-redis
    restart: unless-stopped
    command: ["redis-server", "--appendonly", "yes"]
    volumes:
      - redis-data:/data
    ports:
      - "6379:6379"
    networks:
      - backend-network

  phpmyadmin:
    image: phpmyadmin:5.2-apache
    container_name: pf-phpmyadmin
    restart: unless-stopped
    environment:
      - PMA_HOST=mysql
      - PMA_USER=${MYSQL_USER}
      - PMA_PASSWORD=${MYSQL_PASSWORD}
    ports:
      - "${PHPMYADMIN_PORT}:80"
    networks:
      - backend-network

networks:
  frontend-network:
    driver: bridge
  backend-network:
    driver: bridge

volumes:
  mysql-data:
  redis-data:
```

---

# 🔐 Apache Dockerfile

```dockerfile
FROM httpd:2.4.65-alpine

RUN apk add --no-cache openssl

RUN mkdir -p /usr/local/apache2/certs

RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048     -keyout /usr/local/apache2/certs/server.key     -out /usr/local/apache2/certs/server.crt     -subj "/C=ES/ST=Catalunya/L=Girona/O=ASIX/OU=Dev/CN=frontend.local"

COPY conf/httpd.conf /usr/local/apache2/conf/httpd.conf
COPY conf/vhosts/ /usr/local/apache2/conf/vhosts/
COPY sites/ /usr/local/apache2/sites/

EXPOSE 80 443

CMD ["httpd-foreground"]
```

---

# 📜 apache/conf/httpd.conf

```apache
ServerRoot "/usr/local/apache2"
Listen 80
Listen 443

LoadModule mpm_event_module modules/mod_mpm_event.so
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule ssl_module modules/mod_ssl.so
LoadModule headers_module modules/mod_headers.so
LoadModule log_config_module modules/mod_log_config.so

TypesConfig conf/mime.types
DirectoryIndex index.php index.html

LogFormat "{ "time":"%{%Y-%m-%dT%H:%M:%S}t", "vhost":"%v", "client":"%h", "request":"%r", "status":%>s }" json
CustomLog "logs/access-json.log" json

Include conf/vhosts/*.conf
```

---

# 🌐 Virtual Host Frontend (`frontend.conf`)

```apache
<VirtualHost *:80>
    ServerName frontend.local
    RewriteEngine On
    RewriteRule ^/(.*)$ https://frontend.local/$1 [R=301,L]
</VirtualHost>

<VirtualHost *:443>
    ServerName frontend.local
    DocumentRoot "/usr/local/apache2/sites/frontend"

    SSLEngine on
    SSLCertificateFile "/usr/local/apache2/certs/server.crt"
    SSLCertificateKeyFile "/usr/local/apache2/certs/server.key"

    <Directory "/usr/local/apache2/sites/frontend">
        AllowOverride All
        Require all granted
    </Directory>

    Header always set Strict-Transport-Security "max-age=31536000"
    Header always set X-Frame-Options "SAMEORIGIN"

</VirtualHost>
```

---

# 🌐 Virtual Host API (`api.conf`)

```apache
<VirtualHost *:80>
    ServerName api.local
    RewriteEngine On
    RewriteRule ^/(.*)$ https://api.local/$1 [R=301,L]
</VirtualHost>

<VirtualHost *:443>
    ServerName api.local
    DocumentRoot "/usr/local/apache2/sites/api"

    SSLEngine on
    SSLCertificateFile "/usr/local/apache2/certs/server.crt"
    SSLCertificateKeyFile "/usr/local/apache2/certs/server.key"

    <Directory "/usr/local/apache2/sites/api">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

# 🗄️ MySQL – Fitxer d’inicialització (`01-schema.sql`)

```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100),
  email VARCHAR(150),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE articles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  title VARCHAR(200),
  content TEXT,
  published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

INSERT INTO users (username, email) VALUES
('ayoub', 'ayoub@example.com'),
('admin', 'admin@example.com');

INSERT INTO articles (user_id, title, content) VALUES
(1, 'Primer article', 'Contingut de prova…');
```

---

# 🌍 Frontend `index.php`

*(S'ha omès aquí per espai; el fitxer complet està inclòs a la carpeta final.)*

---

# 🌍 API REST `index.php`

*(També inclòs al projecte final.)*

---

# ▶️ Com executar el projecte

```bash
docker compose up -d --build
```

Accedeix a:

| Servei | URL |
|--------|-----|
| **Frontend** | https://frontend.local |
| **API REST** | https://api.local/api/articles |
| **phpMyAdmin** | http://localhost:8081 |
| **MySQL** | port 3306 |
| **Redis** | port 6379 |

---

# ✔️ Funcionalitats implementades

- Sistema multi-contenidor complet
- HTTPS autosignat
- Redirecció HTTP → HTTPS
- 2 Virtual Hosts separats
- Logs JSON
- Redis per a estadístiques (visites)
- API REST real (GET/POST)
- Inicialització MySQL automàtica
- Variables d’entorn

---

# ✨ Fi del README
Projecte completament operatiu i preparat per a lliurar.

