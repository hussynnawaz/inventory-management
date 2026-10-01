-- Payment Date migration
-- Run on existing inventory database
--
-- Adds customer_payments.payment_date = the date the customer actually paid.
-- This is NOT the invoice date and NOT the sale date; those live on
-- sale_orders.order_date and stay completely independent.
--
-- Existing rows are preserved: the column is nullable and legacy payments are
-- backfilled from the day they were recorded (DATE(created_at)).

USE inventory;

-- MySQL 8.0.29+ supports IF NOT EXISTS; older servers: drop the guard.
ALTER TABLE customer_payments
  ADD COLUMN IF NOT EXISTS payment_date DATE NULL DEFAULT NULL AFTER payment_method;

-- Legacy payments: the only sensible default is the day they were recorded.
UPDATE customer_payments
SET payment_date = DATE(created_at)
WHERE payment_date IS NULL;

ALTER TABLE customer_payments
  ADD INDEX IF NOT EXISTS idx_cust_payments_payment_date (payment_date);