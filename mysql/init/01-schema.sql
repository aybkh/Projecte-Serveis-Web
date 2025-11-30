CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

INSERT INTO users (username, email) VALUES ('admin', 'admin@ayoubjerari.com');
INSERT INTO users (username, email) VALUES ('editor', 'editor@ayoubjerari.com');

INSERT INTO articles (user_id, title, content) VALUES (1, 'Bienvenido a nuestro sitio', 'Este es el primer articulo en nuestra nueva plataforma.');
INSERT INTO articles (user_id, title, content) VALUES (1, 'Docker es increíble', 'Docker Compose facilita la gestion de aplicaciones multi-contenedor.');
INSERT INTO articles (user_id, title, content) VALUES (2, 'Consejos de PHP', 'Aqui tienes algunos consejos para escribir mejor codigo PHP.');
INSERT INTO articles (user_id, title, content) VALUES (2, 'Conceptos basicos de MySQL', 'Entender las bases de datos relacionales es clave.');
INSERT INTO articles (user_id, title, content) VALUES (1, 'Cache con Redis', 'Acelera tu aplicacion con Redis.');
