-- Schema for the loyalty platform (MySQL / MariaDB).
-- Run inside phpMyAdmin or mysql: mysql -u root -p < schema.sql
CREATE DATABASE IF NOT EXISTS loyalty_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE loyalty_app;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    tax_code VARCHAR(32) NOT NULL UNIQUE,
    phone VARCHAR(32) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    postal_code VARCHAR(12) NOT NULL,
    address VARCHAR(180) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_consents (
    user_id INT PRIMARY KEY,
    data_processing TINYINT(1) NOT NULL DEFAULT 0,
    profiling TINYINT(1) NOT NULL DEFAULT 0,
    marketing TINYINT(1) NOT NULL DEFAULT 0,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    sku VARCHAR(60) NOT NULL UNIQUE,
    ean VARCHAR(32) NOT NULL UNIQUE,
    brand VARCHAR(120) DEFAULT NULL,
    category VARCHAR(120) DEFAULT NULL,
    description TEXT,
    image_url VARCHAR(255) DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    active TINYINT(1) DEFAULT 1,
    odoo_product_id VARCHAR(64) DEFAULT NULL,
    shopify_product_id VARCHAR(64) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_products_ean (ean)
);

CREATE TABLE personalized_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_name VARCHAR(120) NOT NULL,
    product_ean VARCHAR(32) NOT NULL,
    note VARCHAR(255),
    yearly_changes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_offers_ean (product_ean)
);

CREATE TABLE offer_change_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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

INSERT INTO users (first_name, last_name, tax_code, phone, email, postal_code, address, password_hash, role)
VALUES ('Admin', 'User', 'AAAAAA00A00A000A', '+39000000000', 'admin@example.com', '20100', 'Via Demo 1, Milano',
        '$2y$10$sTWX7qAFR5P20/a5ZC7hyOJ/pb5qnlO3QEqdi6I.Quv9uUOAhXzJ2', 'admin');
-- Password: admin123

INSERT INTO user_consents (user_id, data_processing, profiling, marketing)
VALUES (1, 1, 1, 0);

INSERT INTO loyalty_balances (user_id, points, level) VALUES (1, 1200, 'Oro');
INSERT INTO orders (user_id, order_number, total) VALUES (1, 'ORD-1001', 89.90);
INSERT INTO coupons (user_id, code, description, discount_percent, expires_at)
VALUES (1, 'WELCOME10', '10% di benvenuto', 10, DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY));

INSERT INTO products (name, sku, ean, brand, category, description, image_url, price, active, odoo_product_id, shopify_product_id) VALUES
('Caffè in grani', 'CAFF-GRANI-001', '8000000000011', 'Torrefazione Demo', 'Dispensa', 'Miscela 100% arabica per moka e espresso.', 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=400&q=60', 7.90, 1, '101', 'gid://shopify/Product/111111111'),
('Snack bio', 'SNCK-BIO-002', '8000000000028', 'Green Snacks', 'Alimentari', 'Barretta biologica con frutta secca e cereali.', 'https://images.unsplash.com/photo-1585238341986-1e3b71ff0af8?auto=format&fit=crop&w=400&q=60', 3.20, 1, '102', 'gid://shopify/Product/222222222'),
('Detersivo eco', 'DETR-ECO-003', '8000000000035', 'Eco Home', 'Cura casa', 'Detersivo ecologico concentrato per bucato.', 'https://images.unsplash.com/photo-1582719478248-54e9f2af4b03?auto=format&fit=crop&w=400&q=60', 5.50, 1, '103', 'gid://shopify/Product/333333333');

INSERT INTO personalized_offers (user_id, product_name, product_ean, note, yearly_changes)
VALUES (1, 'Caffè in grani', '8000000000011', 'Prezzo dedicato', 1);
INSERT INTO offer_change_log (user_id) VALUES (1);
