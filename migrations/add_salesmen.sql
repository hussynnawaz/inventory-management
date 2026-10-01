-- Salesmen module migration
-- Run on existing inventory database

USE inventory;

CREATE TABLE IF NOT EXISTS salesmen (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  code       VARCHAR(30)  NOT NULL UNIQUE,
  name       VARCHAR(120) NOT NULL,
  phone      VARCHAR(30)  DEFAULT '',
  cnic       VARCHAR(20)  DEFAULT '',
  email      VARCHAR(120) DEFAULT '',
  address    TEXT,
  notes      VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE sale_orders
  ADD COLUMN IF NOT EXISTS salesman_id    INT NULL AFTER delivery_route,
  ADD COLUMN IF NOT EXISTS salesman_code  VARCHAR(30) DEFAULT '' AFTER salesman_id,
  ADD COLUMN IF NOT EXISTS salesman_phone VARCHAR(30) DEFAULT '' AFTER salesman,
  ADD COLUMN IF NOT EXISTS salesman_cnic  VARCHAR(20) DEFAULT '' AFTER salesman_phone;
