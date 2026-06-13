-- ════════════════════════════════════════════════════════
--  GadgetZone — Database Setup (fresh install: includes everything)
-- ════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS gadgetzone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gadgetzone;

-- Users
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(80)  NOT NULL,
    last_name  VARCHAR(80)  NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    phone      VARCHAR(20),
    address    TEXT,
    city       VARCHAR(100),
    avatar     VARCHAR(255) DEFAULT NULL,
    role       ENUM('member','admin','super_admin') DEFAULT 'member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories
CREATE TABLE IF NOT EXISTS categories (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(10)  NOT NULL DEFAULT '📦'
);

-- Products
CREATE TABLE IF NOT EXISTS products (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    category_id   INT            NOT NULL,
    name          VARCHAR(200)   NOT NULL,
    slug          VARCHAR(200)   NOT NULL UNIQUE,
    description   TEXT,
    price         DECIMAL(10,2)  NOT NULL,
    old_price     DECIMAL(10,2),
    image_url     VARCHAR(500)   NOT NULL DEFAULT '',
    badge         ENUM('NEW','HOT','SALE','') DEFAULT '',
    stock         INT            NOT NULL DEFAULT 100,
    featured      TINYINT(1)     DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Orders  (includes Stripe columns for fresh install)
CREATE TABLE IF NOT EXISTS orders (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT,
    order_number      VARCHAR(60)  NOT NULL UNIQUE,
    total_amount      DECIMAL(10,2) NOT NULL,
    status            ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    payment_method    VARCHAR(40)  NOT NULL,
    shipping_address  TEXT         NOT NULL,
    notes             TEXT,
    stripe_session_id VARCHAR(200) DEFAULT NULL,
    payment_status    ENUM('unpaid','paid','refunded') DEFAULT 'unpaid',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Settings (currency, Stripe keys, etc.)
CREATE TABLE IF NOT EXISTS settings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT         NOT NULL,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Default settings (currency + Stripe placeholders)
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('active_currency',        'BDT'),
('stripe_publishable_key', 'pk_test_REPLACE_WITH_YOUR_KEY'),
('stripe_secret_key',      'sk_test_REPLACE_WITH_YOUR_KEY'),
('stripe_webhook_secret',  '');

-- Order Items
CREATE TABLE IF NOT EXISTS order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT            NOT NULL,
    product_id INT            NOT NULL,
    quantity   INT            NOT NULL,
    price      DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ── Seed data ──────────────────────────────────────────────

INSERT IGNORE INTO users (first_name, last_name, email, password, phone, address, city, role) VALUES
('Super', 'Admin', 'admin@gadgetzone.com',
 '$2y$10$IHDQwagf1JxAncjpyZj5oefo9utzJ7AQG0bez9kMXj2aEexbnIFBa',
 NULL, NULL, NULL, 'super_admin'),
('Demo',  'User',  'demo@gadgetzone.com',
 '$2y$12$ImgKcQQ2W/RUlawMQn/0POCl.vSavJ5u51R8Hbtd8NxH3/WRRLPIG',
 '081-234-5678', '123 Demo Street, Bangkok', 'Bangkok', 'member');
-- Admin password: Admin@1234
-- Demo  password: Demo@1234

INSERT IGNORE INTO categories (name, slug, icon) VALUES
('Smartphones', 'smartphones', '📱'),
('Laptops',     'laptops',     '💻'),
('Audio',       'audio',       '🎧'),
('Cameras',     'cameras',     '📷'),
('Wearables',   'wearables',   '⌚'),
('Accessories', 'accessories', '🔌');

INSERT IGNORE INTO products (category_id, name, slug, description, price, old_price, image_url, badge, stock, featured) VALUES
(1, 'iPhone 15 Pro Max',           'iphone-15-pro-max',          'Apple flagship with A17 Pro chip, titanium build, and 48MP camera system.',           149999, 164999, 'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=600&q=80', 'HOT',  45, 1),
(1, 'Samsung Galaxy S24 Ultra',    'galaxy-s24-ultra',           'Snapdragon 8 Gen 3, 200MP camera, built-in S Pen, 12GB RAM.',                          134999, 149999, 'https://images.unsplash.com/photo-1610945264803-c22b62d2a7b3?w=600&q=80', 'NEW',  30, 1),
(1, 'Google Pixel 8 Pro',          'google-pixel-8-pro',         'Pure Android experience with AI-enhanced Tensor G3 chip and 50MP camera.',               89999, NULL,   'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=600&q=80', '',     60, 0),
(2, 'MacBook Air M3',              'macbook-air-m3',             'Fanless design, up to 18hr battery, stunning Liquid Retina display.',                   139999, 154999, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=600&q=80', 'SALE', 20, 1),
(2, 'Dell XPS 15',                 'dell-xps-15',                'OLED touch display, Intel Core i9, 32GB DDR5, RTX 4060 GPU.',                           189999, NULL,   'https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?w=600&q=80', 'NEW',  15, 1),
(2, 'ASUS ROG Zephyrus G14',       'asus-rog-zephyrus-g14',      'AMD Ryzen 9, RTX 4090, 165Hz QHD panel — the ultimate gaming laptop.',                  179999, 189999, 'https://images.unsplash.com/photo-1612287230202-1ff1d85d1bdf?w=600&q=80', 'SALE', 10, 0),
(3, 'Sony WH-1000XM5',             'sony-wh-1000xm5',            'Industry-leading noise cancellation with 30hr battery and multipoint connection.',       34999,  39999,  'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&q=80', 'HOT',  80, 1),
(3, 'Apple AirPods Pro 2',         'airpods-pro-2',              'Adaptive Transparency, Personalized Spatial Audio, USB-C charging case.',                29999,  NULL,   'https://images.unsplash.com/photo-1600294037681-c80b4cb5b434?w=600&q=80', '',     100,0),
(3, 'JBL Charge 5 Speaker',        'jbl-charge-5',               'IP67 waterproof portable speaker with 20hr playtime and power bank feature.',             9999,  12999,  'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=600&q=80', 'SALE', 50, 0),
(4, 'Sony A7 IV Mirrorless',       'sony-a7-iv',                 '33MP full-frame sensor, 4K 60fps video, advanced autofocus system.',                    249999, NULL,   'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=600&q=80', 'NEW',  8,  1),
(4, 'Canon EOS R6 Mark II',        'canon-eos-r6-mark-ii',       '40fps burst, in-body stabilisation, dual card slots, 4K HQ video.',                    219999, 234999, 'https://images.unsplash.com/photo-1502920917128-1aa500764bed?w=600&q=80', '',     12, 0),
(5, 'Apple Watch Ultra 2',         'apple-watch-ultra-2',        'Titanium case, 60hr battery, dual-frequency GPS, ocean band.',                           89999,  NULL,   'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=600&q=80', 'HOT',  35, 1),
(5, 'Samsung Galaxy Watch 6',      'samsung-galaxy-watch-6',     'Advanced health tracking, sapphire glass, BioActive sensor, Wear OS.',                   29999,  34999,  'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&q=80', 'SALE', 55, 0),
(6, 'Anker 140W GaN Charger',      'anker-140w-gan-charger',     'Three ports, PowerIQ 4.0, charges MacBook + iPhone + iPad simultaneously.',               4999,   6499,  'https://images.unsplash.com/photo-1625772452859-1c03d5bf1137?w=600&q=80', 'NEW',  200,0),
(6, 'Samsung 45W USB-C Cable',     'samsung-45w-usb-c-cable',    'Premium braided 2m cable with 45W fast charging and 10Gbps data transfer.',                999,   NULL,   'https://images.unsplash.com/photo-1585771724684-38269d6639fd?w=600&q=80', '',     500,0);
