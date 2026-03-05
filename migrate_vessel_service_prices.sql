-- Migration: Add price columns to service_categories and vessel_services
-- Run this once on your database (local & hosting)
-- Date: 2026-03-01

-- 1. Default/base fare on the service category itself
ALTER TABLE service_categories
    ADD COLUMN IF NOT EXISTS base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER avg_service_time;

-- 2. Per-vessel price override on the vessel↔service mapping
ALTER TABLE vessel_services
    ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER service_category_id,
    ADD COLUMN IF NOT EXISTS notes VARCHAR(255) NULL AFTER price;
