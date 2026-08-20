-- Migration: Add salesmen table and rename delivery_route to destination
-- Run this on the inventory database

-- 1. Create salesmen table
CREATE TABLE IF NOT EXISTS salesmen (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  salesman_id  VARCHAR(30)  NOT NULL UNIQUE,
  name         VARCHAR(120) NOT NULL,
  phone        VARCHAR(30)  DEFAULT '',
  cnic         VARCHAR(20)  DEFAULT '',
  address      TEXT,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Rename delivery_route to destination in customers
ALTER TABLE customers CHANGE COLUMN delivery_route destination VARCHAR(80) DEFAULT '';

-- 3. Add salesman_id column to sale_orders (if not exists)
-- ALTER TABLE sale_orders ADD COLUMN salesman_id INT NULL AFTER salesman;
