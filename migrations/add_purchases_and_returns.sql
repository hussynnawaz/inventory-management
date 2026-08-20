-- Migration: Add purchases and returns enhanced tables and updates
-- Date: 2026-08-20

USE inventory;

-- Ensure suppliers table exists
CREATE TABLE IF NOT EXISTS suppliers (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(120) NOT NULL,
  phone      VARCHAR(30)  DEFAULT '',
  email      VARCHAR(120) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure purchases table exists
CREATE TABLE IF NOT EXISTS purchases (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  reference_no VARCHAR(50) NOT NULL UNIQUE,
  supplier_id  INT NULL,
  user_id      INT NOT NULL,
  total        DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure purchase_items table exists
CREATE TABLE IF NOT EXISTS purchase_items (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  purchase_id  INT NOT NULL,
  product_id   INT NOT NULL,
  quantity     INT NOT NULL,
  cost         DECIMAL(12,2) NOT NULL,
  line_total   DECIMAL(12,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (purchase_id) REFERENCES purchases(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure returns table exists and supports returns details
CREATE TABLE IF NOT EXISTS returns (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  return_no    VARCHAR(50) NOT NULL UNIQUE,
  sale_order_id INT NULL,
  product_id   INT NOT NULL,
  quantity     INT NOT NULL,
  refund_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  line_total   DECIMAL(12,2) NOT NULL DEFAULT 0,
  reason       VARCHAR(255) DEFAULT '',
  user_id      INT NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
