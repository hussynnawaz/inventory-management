-- =========================================================================
-- MJ Traders Inventory Management System
-- Master Database Schema
-- =========================================================================
-- Usage:
--   1. Open phpMyAdmin or MySQL CLI
--   2. Import this file
--   3. Default admin login: admin / admin123
--
-- Safe to re-run: drops and recreates all tables.
-- =========================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS inventory
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;

USE inventory;

-- =========================================================================
-- 1. USERS
-- =========================================================================
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(50)  NOT NULL,
  password   VARCHAR(255) NOT NULL,
  full_name  VARCHAR(100) NOT NULL DEFAULT '',
  role       VARCHAR(20)  NOT NULL DEFAULT 'staff',
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- 2. CUSTOMERS
-- =========================================================================
DROP TABLE IF EXISTS customers;
CREATE TABLE customers (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  code         VARCHAR(30)  NOT NULL DEFAULT '',
  name         VARCHAR(120) NOT NULL,
  contact      VARCHAR(30)  DEFAULT NULL,
  destination  VARCHAR(80)  DEFAULT NULL,
  salesman     VARCHAR(80)  DEFAULT NULL,
  ntn_no       VARCHAR(30)  DEFAULT NULL,
  sales_tax_no VARCHAR(30)  DEFAULT NULL,
  cnic         VARCHAR(20)  DEFAULT NULL,
  address      TEXT         DEFAULT NULL,
  email        VARCHAR(120) DEFAULT NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_customers_code (code),
  INDEX idx_customers_name (name),
  INDEX idx_customers_contact (contact)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- 3. SUPPLIERS
-- =========================================================================
DROP TABLE IF EXISTS suppliers;
CREATE TABLE suppliers (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  code           VARCHAR(30)    NOT NULL DEFAULT '',
  name           VARCHAR(120)   NOT NULL,
  company_name   VARCHAR(120)   DEFAULT NULL,
  contact        VARCHAR(30)    DEFAULT NULL,
  phone          VARCHAR(30)    DEFAULT NULL,
  email          VARCHAR(120)   DEFAULT NULL,
  address        TEXT           DEFAULT NULL,
  city           VARCHAR(80)    DEFAULT NULL,
  ntn            VARCHAR(30)    DEFAULT NULL,
  stn            VARCHAR(30)    DEFAULT NULL,
  opening_balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  status         VARCHAR(20)    NOT NULL DEFAULT 'active',
  notes          TEXT           DEFAULT NULL,
  created_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_suppliers_code (code),
  INDEX idx_suppliers_name (name),
  INDEX idx_suppliers_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- 4. PRODUCTS
-- =========================================================================
DROP TABLE IF EXISTS products;
CREATE TABLE products (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(150)    NOT NULL,
  sku          VARCHAR(50)     NOT NULL,
  category     VARCHAR(80)     NOT NULL DEFAULT '',
  description  TEXT            DEFAULT NULL,
  cost_price   DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
  sale_price   DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
  quantity     INT             NOT NULL DEFAULT 0,
  created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_products_sku (sku),
  INDEX idx_products_name (name),
  INDEX idx_products_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- 5. SALESMEN
-- =========================================================================
DROP TABLE IF EXISTS salesmen;
CREATE TABLE salesmen (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  salesman_id  VARCHAR(30)  NOT NULL,
  name         VARCHAR(120) NOT NULL,
  phone        VARCHAR(30)  DEFAULT NULL,
  cnic         VARCHAR(20)  DEFAULT NULL,
  email        VARCHAR(120) DEFAULT NULL,
  address      TEXT         DEFAULT NULL,
  notes        VARCHAR(255) DEFAULT NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_salesmen_salesman_id (salesman_id),
  INDEX idx_salesmen_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- 6. SALES (legacy simple sales)
-- =========================================================================
DROP TABLE IF EXISTS sales;
CREATE TABLE sales (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  invoice_no   VARCHAR(50) NOT NULL,
  customer_id  INT         DEFAULT NULL,
  user_id      INT         NOT NULL,
  total        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  created_at   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_sales_invoice_no (invoice_no),
  INDEX idx_sales_customer_id (customer_id),
  CONSTRAINT fk_sales_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- 7. SALE ITEMS (legacy simple sales)
-- =========================================================================
DROP TABLE IF EXISTS sale_items;
CREATE TABLE sale_items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  sale_id    INT           NOT NULL,
  product_id INT           NOT NULL,
  quantity   INT           NOT NULL,
  price      DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_sale_items_sale    FOREIGN KEY (sale_id)    REFERENCES sales(id)    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- 8. SALE ORDERS (main sales with full customer/salesman details)
-- =========================================================================
DROP TABLE IF EXISTS sale_orders;
CREATE TABLE sale_orders (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  order_no          VARCHAR(50)    NOT NULL,
  order_date        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  customer_id       INT            DEFAULT NULL,
  customer_code     VARCHAR(30)    DEFAULT NULL,
  customer_name     VARCHAR(120)   DEFAULT NULL,
  contact           VARCHAR(30)    DEFAULT NULL,
  destination       VARCHAR(80)    DEFAULT NULL,
  delivery_route    VARCHAR(80)    DEFAULT NULL,
  salesman_id       INT            DEFAULT NULL,
  salesman_code     VARCHAR(30)    DEFAULT NULL,
  salesman          VARCHAR(80)    DEFAULT NULL,
  salesman_phone    VARCHAR(30)    DEFAULT NULL,
  salesman_cnic     VARCHAR(20)    DEFAULT NULL,
  ntn_no            VARCHAR(30)    DEFAULT NULL,
  sales_tax_no      VARCHAR(30)    DEFAULT NULL,
  cnic              VARCHAR(20)    DEFAULT NULL,
  address           TEXT           DEFAULT NULL,
  subtotal          DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  sales_tax_pct     DECIMAL(5,2)   NOT NULL DEFAULT 0.00,
  sales_tax_amt     DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  advanced_tax_pct  DECIMAL(5,2)   NOT NULL DEFAULT 0.00,
  advanced_tax_amt  DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  gst_pct           DECIMAL(5,2)   NOT NULL DEFAULT 0.00,
  gst_amt           DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  total             DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  user_id           INT            NOT NULL,
  created_at        TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_sale_orders_order_no (order_no),
  INDEX idx_sale_orders_customer_id (customer_id),
  INDEX idx_sale_orders_salesman_id (salesman_id),
  INDEX idx_sale_orders_user_id (user_id),
  INDEX idx_sale_orders_order_date (order_date),
  CONSTRAINT fk_sale_orders_user      FOREIGN KEY (user_id)     REFERENCES users(id)      ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_sale_orders_customer  FOREIGN KEY (customer_id) REFERENCES customers(id)  ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_sale_orders_salesman  FOREIGN KEY (salesman_id) REFERENCES salesmen(id)    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- 9. SALE ORDER ITEMS
-- =========================================================================
DROP TABLE IF EXISTS sale_order_items;
CREATE TABLE sale_order_items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  sale_order_id INT            NOT NULL,
  product_id    INT            NOT NULL,
  product_name  VARCHAR(150)   DEFAULT NULL,
  quantity      INT            NOT NULL,
  price         DECIMAL(12,2)  NOT NULL,
  line_total    DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_soi_order   FOREIGN KEY (sale_order_id) REFERENCES sale_orders(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_soi_product FOREIGN KEY (product_id)    REFERENCES products(id)    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- 10. PURCHASES
-- =========================================================================
DROP TABLE IF EXISTS purchases;
CREATE TABLE purchases (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  reference_no VARCHAR(50)    NOT NULL,
  supplier_id  INT            DEFAULT NULL,
  user_id      INT            NOT NULL,
  total        DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  created_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_purchases_reference_no (reference_no),
  INDEX idx_purchases_supplier_id (supplier_id),
  INDEX idx_purchases_user_id (user_id),
  CONSTRAINT fk_purchases_user     FOREIGN KEY (user_id)     REFERENCES users(id)      ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_purchases_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id)  ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- 11. PURCHASE ITEMS
-- =========================================================================
DROP TABLE IF EXISTS purchase_items;
CREATE TABLE purchase_items (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  purchase_id INT            NOT NULL,
  product_id  INT            NOT NULL,
  quantity    INT            NOT NULL,
  cost        DECIMAL(12,2)  NOT NULL,
  line_total  DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_pi_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_pi_product  FOREIGN KEY (product_id)  REFERENCES products(id)  ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- 12. RETURNS
-- =========================================================================
DROP TABLE IF EXISTS returns;
CREATE TABLE `returns` (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  return_no      VARCHAR(50)    NOT NULL,
  sale_order_id  INT            DEFAULT NULL,
  sale_id        INT            DEFAULT NULL,
  product_id     INT            NOT NULL,
  quantity       INT            NOT NULL,
  refund_price   DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  line_total     DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  reason         VARCHAR(255)   DEFAULT NULL,
  user_id        INT            NOT NULL,
  created_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_returns_return_no (return_no),
  INDEX idx_returns_sale_order_id (sale_order_id),
  INDEX idx_returns_product_id (product_id),
  INDEX idx_returns_user_id (user_id),
  CONSTRAINT fk_returns_product      FOREIGN KEY (product_id)    REFERENCES products(id)     ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_returns_user         FOREIGN KEY (user_id)       REFERENCES users(id)        ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_returns_sale_order   FOREIGN KEY (sale_order_id) REFERENCES sale_orders(id)  ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- 13. CUSTOMER PAYMENTS
-- =========================================================================
DROP TABLE IF EXISTS customer_payments;
CREATE TABLE customer_payments (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  receipt_no        VARCHAR(50)    NOT NULL,
  customer_id       INT            NOT NULL,
  sale_order_id     INT            DEFAULT NULL,
  payment_method    VARCHAR(20)    NOT NULL DEFAULT 'cash',
  amount            DECIMAL(12,2)  NOT NULL,
  previous_balance  DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  remaining_balance DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  collector_name    VARCHAR(120)   DEFAULT NULL,
  transaction_id    VARCHAR(100)   DEFAULT NULL,
  bank_channel      VARCHAR(120)   DEFAULT NULL,
  notes             TEXT           DEFAULT NULL,
  user_id           INT            NOT NULL,
  created_at        TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cust_payments_receipt (receipt_no),
  INDEX idx_cust_payments_customer_id (customer_id),
  INDEX idx_cust_payments_order_id (sale_order_id),
  INDEX idx_cust_payments_created (created_at),
  CONSTRAINT fk_cust_payments_customer FOREIGN KEY (customer_id)   REFERENCES customers(id)  ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_cust_payments_order    FOREIGN KEY (sale_order_id) REFERENCES sale_orders(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_cust_payments_user     FOREIGN KEY (user_id)       REFERENCES users(id)       ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- SEED DATA: default admin account (password: admin123)
-- =========================================================================
INSERT INTO users (username, password, full_name, role)
VALUES ('admin', '$2y$10$gi6z6KNXb/PPf1MrjG/Hn..1VSkWZ.WbFJ8yDTMuwoIBEz91fPhfW', 'Administrator', 'admin');

SET FOREIGN_KEY_CHECKS = 1;
