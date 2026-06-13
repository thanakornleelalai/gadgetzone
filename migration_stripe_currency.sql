-- ════════════════════════════════════════════════════════
--  GadgetZone — Migration: settings table + Stripe columns
--  Run this only on an EXISTING database created before Stripe support.
--  Fresh installs already get everything from database_setup.sql.
-- ════════════════════════════════════════════════════════
USE gadgetzone;

-- Settings table (stores currency & Stripe keys)
CREATE TABLE IF NOT EXISTS settings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT         NOT NULL,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Stripe columns on orders table (MySQL 8.0.29+ / MariaDB 10.5+ support IF NOT EXISTS)
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS stripe_session_id VARCHAR(200) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS payment_status    ENUM('unpaid','paid','refunded') DEFAULT 'unpaid';

-- Default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('active_currency',        'BDT'),
('stripe_publishable_key', 'pk_test_REPLACE_WITH_YOUR_KEY'),
('stripe_secret_key',      'sk_test_REPLACE_WITH_YOUR_KEY'),
('stripe_webhook_secret',  '')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
