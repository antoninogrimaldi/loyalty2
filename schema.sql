-- Schema for the minimal loyalty platform (MySQL / MariaDB).
-- Run inside phpMyAdmin or mysql: mysql -u root -p < schema.sql
CREATE DATABASE IF NOT EXISTS loyalty_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE loyalty_app;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    tax_code VARCHAR(32) NOT NULL,
    phone VARCHAR(32) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    postal_code VARCHAR(12) NOT NULL,
    address VARCHAR(180) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE loyalty_balances (
    user_id INT PRIMARY KEY,
    points INT NOT NULL DEFAULT 0,
    level VARCHAR(60) DEFAULT 'Bronze',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    code VARCHAR(40) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL,
    discount_percent INT DEFAULT 0,
    expires_at DATE,
    is_redeemed TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE personalized_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_name VARCHAR(120) NOT NULL,
    note VARCHAR(255),
    yearly_changes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(40) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    sku VARCHAR(60) UNIQUE,
    price DECIMAL(10,2) NOT NULL,
    active TINYINT(1) DEFAULT 1
);

INSERT INTO users (first_name, last_name, tax_code, phone, email, postal_code, address, password_hash, role)
VALUES ('Admin', 'User', 'AAAAAA00A00A000A', '+39000000000', 'admin@example.com', '20100', 'Via Demo 1, Milano',
        '$2y$10$sTWX7qAFR5P20/a5ZC7hyOJ/pb5qnlO3QEqdi6I.Quv9uUOAhXzJ2', 'admin');
-- Password: admin123

INSERT INTO loyalty_balances (user_id, points, level) VALUES (1, 1200, 'Oro');
INSERT INTO orders (user_id, order_number, total) VALUES (1, 'ORD-1001', 89.90);
INSERT INTO coupons (user_id, code, description, discount_percent, expires_at)
VALUES (1, 'WELCOME10', '10% di benvenuto', 10, DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY));
INSERT INTO personalized_offers (user_id, product_name, note)
VALUES (1, 'Caffè in grani', 'Prezzo dedicato');
