-- Fix purchase_orders.id - add AUTO_INCREMENT
-- Run this in phpMyAdmin if the automatic fix doesn't work

-- Step 1: Fix purchase_orders table
ALTER TABLE purchase_orders MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;

-- Step 2: Fix purchase_order_items table
ALTER TABLE purchase_order_items MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;
