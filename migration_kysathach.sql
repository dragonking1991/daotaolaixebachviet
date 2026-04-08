-- Migration: Add kỳ sát hạch management table
-- Run this SQL if the table doesn't exist yet

CREATE TABLE IF NOT EXISTS table_kysathach (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ngay_sathach DATE NOT NULL,
  ten_viettat VARCHAR(255) NOT NULL,
  loai_sathach VARCHAR(100) NOT NULL,
  ngaytao INT DEFAULT 0,
  user_tao VARCHAR(255) DEFAULT '',
  stt INT DEFAULT 0,
  hienthi TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add id_kysathach to product table for linking QR records to kỳ sát hạch
-- ALTER TABLE table_product ADD COLUMN id_kysathach INT DEFAULT 0;
