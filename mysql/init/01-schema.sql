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

INSERT INTO users (username, email) VALUES ('admin', 'admin@example.com');
INSERT INTO users (username, email) VALUES ('editor', 'editor@example.com');

INSERT INTO articles (user_id, title, content) VALUES (1, 'Welcome to our Site', 'This is the first article on our new platform.');
INSERT INTO articles (user_id, title, content) VALUES (1, 'Docker is Awesome', 'Docker Compose makes managing multi-container applications easy.');
INSERT INTO articles (user_id, title, content) VALUES (2, 'PHP Tips', 'Here are some tips for writing better PHP code.');
INSERT INTO articles (user_id, title, content) VALUES (2, 'MySQL Basics', 'Understanding relational databases is key.');
INSERT INTO articles (user_id, title, content) VALUES (1, 'Redis Caching', 'Speed up your app with Redis.');
