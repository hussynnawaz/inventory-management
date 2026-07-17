-- MJ Traders Inventory Management System
-- Database: inventory
-- Run this on XAMPP (phpMyAdmin / mysql) to create schema and seed admin.

CREATE DATABASE IF NOT EXISTS inventory
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;

USE inventory;

-- ---------------------------------------------------------------------------
-- Users
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(50)  NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  full_name  VARCHAR(100) NOT NULL DEFAULT '',
  role       VARCHAR(20)  NOT NULL DEFAULT 'staff',
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin account: admin / admin123
INSERT INTO users (username, password, full_name, role)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

-- ---------------------------------------------------------------------------
-- Products (inventory)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS products;
CREATE TABLE products (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(150) NOT NULL,
  sku          VARCHAR(50)  NOT NULL UNIQUE,
  category     VARCHAR(80)  NOT NULL DEFAULT '',
  description  TEXT,
  cost_price   DECIMAL(12,2) NOT NULL DEFAULT 0,
  sale_price   DECIMAL(12,2) NOT NULL DEFAULT 0,
  quantity     INT NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Customers & Suppliers (optional but handy for reports)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS customers;
CREATE TABLE customers (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(120) NOT NULL,
  phone      VARCHAR(30)  DEFAULT '',
  email      VARCHAR(120) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS suppliers;
CREATE TABLE suppliers (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(120) NOT NULL,
  phone      VARCHAR(30)  DEFAULT '',
  email      VARCHAR(120) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Sales
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS sales;
CREATE TABLE sales (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  invoice_no   VARCHAR(50) NOT NULL UNIQUE,
  customer_id  INT NULL,
  user_id      INT NOT NULL,
  total        DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS sale_items;
CREATE TABLE sale_items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  sale_id    INT NOT NULL,
  product_id INT NOT NULL,
  quantity   INT NOT NULL,
  price      DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (sale_id) REFERENCES sales(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Purchases
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS purchases;
CREATE TABLE purchases (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  reference_no VARCHAR(50) NOT NULL UNIQUE,
  supplier_id  INT NULL,
  user_id      INT NOT NULL,
  total        DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS purchase_items;
CREATE TABLE purchase_items (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  purchase_id  INT NOT NULL,
  product_id   INT NOT NULL,
  quantity     INT NOT NULL,
  cost         DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (purchase_id) REFERENCES purchases(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Returns (sales returns)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS returns;
CREATE TABLE returns (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  return_no    VARCHAR(50) NOT NULL UNIQUE,
  sale_id      INT NULL,
  product_id   INT NOT NULL,
  quantity     INT NOT NULL,
  reason       VARCHAR(255) DEFAULT '',
  user_id      INT NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
