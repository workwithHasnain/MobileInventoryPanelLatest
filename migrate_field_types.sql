-- Migration script to fix field types in phones table
-- Run this after updating the database schema

-- Update main_camera_flash from BOOLEAN to VARCHAR
ALTER TABLE phones ALTER COLUMN main_camera_flash TYPE VARCHAR(50);

-- Update refresh_rate from INTEGER to VARCHAR
ALTER TABLE phones ALTER COLUMN refresh_rate TYPE VARCHAR(50);

-- Update display_density from VARCHAR to INTEGER
ALTER TABLE phones ALTER COLUMN display_density TYPE INTEGER USING display_density::INTEGER;

-- Update ram from VARCHAR to DECIMAL
ALTER TABLE phones ALTER COLUMN ram TYPE DECIMAL(5,1) USING ram::DECIMAL(5,1);

-- Update storage from VARCHAR to INTEGER
ALTER TABLE phones ALTER COLUMN storage TYPE INTEGER USING storage::INTEGER;

-- Update main_camera_resolution from VARCHAR to DECIMAL
ALTER TABLE phones ALTER COLUMN main_camera_resolution TYPE DECIMAL(5,1) USING main_camera_resolution::DECIMAL(5,1);

-- Update selfie_camera_resolution from VARCHAR to DECIMAL
ALTER TABLE phones ALTER COLUMN selfie_camera_resolution TYPE DECIMAL(5,1) USING selfie_camera_resolution::DECIMAL(5,1);

-- Update wired_charging from VARCHAR to INTEGER
ALTER TABLE phones ALTER COLUMN wired_charging TYPE INTEGER USING wired_charging::INTEGER;

-- Update wireless_charging from VARCHAR to INTEGER
ALTER TABLE phones ALTER COLUMN wireless_charging TYPE INTEGER USING wireless_charging::INTEGER;

-- Update main_camera_f_number from VARCHAR to DECIMAL
ALTER TABLE phones ALTER COLUMN main_camera_f_number TYPE DECIMAL(3,1) USING main_camera_f_number::DECIMAL(3,1);

-- Add comments to document the changes
COMMENT ON COLUMN phones.main_camera_flash IS 'Flash type: LED, Dual-LED, Xenon, etc.';
COMMENT ON COLUMN phones.refresh_rate IS 'Display refresh rate: 90Hz, 120Hz, etc.';
COMMENT ON COLUMN phones.display_density IS 'Display density in PPI';
COMMENT ON COLUMN phones.ram IS 'RAM in GB';
COMMENT ON COLUMN phones.storage IS 'Storage in GB';
COMMENT ON COLUMN phones.main_camera_resolution IS 'Main camera resolution in MP';
COMMENT ON COLUMN phones.selfie_camera_resolution IS 'Selfie camera resolution in MP';
COMMENT ON COLUMN phones.wired_charging IS 'Wired charging power in W';
COMMENT ON COLUMN phones.wireless_charging IS 'Wireless charging power in W';
COMMENT ON COLUMN phones.main_camera_f_number IS 'Main camera F-number (e.g., 1.8)';
