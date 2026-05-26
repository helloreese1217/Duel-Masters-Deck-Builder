CREATE DATABASE IF NOT EXISTS duel_masters_db;
USE duel_masters_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE decks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    deck_name VARCHAR(100) NOT NULL,
    cover_image VARCHAR(255),
    card_list TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    civilization VARCHAR(50),
    cost INT,
    type VARCHAR(50),
    image_url VARCHAR(255)
);
