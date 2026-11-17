-- ============================================================
-- BeanFlow: full database setup (rebuilds the deleted XAMPP DB)
-- Matches connection.php: host=localhost, user=root, no password,
-- database=beanflow_db
--
-- How to run: phpMyAdmin -> "Import" tab -> choose this file -> Go
-- Safe to re-run: uses IF NOT EXISTS and never drops anything.
-- ============================================================

CREATE DATABASE IF NOT EXISTS beanflow_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE beanflow_db;

-- ------------------------------------------------------------
-- register  (used by register.php, login.php, user_data.php, orders.php)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS register (
    id        INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fullname  VARCHAR(100) NOT NULL,
    email     VARCHAR(150) NOT NULL,
    password  VARCHAR(255) NOT NULL,                 -- password_hash() output
    reg_date  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_register_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- stock  (used by stock.php)
-- Item_Name is the key: stock.php edits/deletes by Item_Name and
-- reports "Item name might already exist" on a duplicate insert.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stock (
    Item_Name  VARCHAR(100) NOT NULL PRIMARY KEY,
    Quantity   INT          NOT NULL DEFAULT 0,
    Price      INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- orders  (used by place_order.php, orders.php)
-- Identical to your original orders_schema.sql
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NULL,                          -- NULL = guest / walk-in order
    items         TEXT NOT NULL,                     -- JSON-encoded array: title, priceUsd, quantity, ...
    total_amount  DECIMAL(10,2) NOT NULL,
    status        VARCHAR(20) NOT NULL DEFAULT 'Pending',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                  ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES register(id)
        ON DELETE SET NULL,
    INDEX idx_orders_status (status),
    INDEX idx_orders_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;