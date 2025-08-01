CREATE DATABASE street_db;
USE street_db;

CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description TEXT,
    latitude DOUBLE,
    longitude DOUBLE,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
