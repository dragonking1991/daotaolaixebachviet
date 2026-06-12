-- Migration: payroll employee import v2 (idempotent)
-- Safe to run multiple times.

DROP PROCEDURE IF EXISTS add_column_if_missing;
DELIMITER $$
CREATE PROCEDURE add_column_if_missing(
	IN p_table VARCHAR(64),
	IN p_column VARCHAR(64),
	IN p_definition TEXT
)
BEGIN
	IF NOT EXISTS (
		SELECT 1
		FROM INFORMATION_SCHEMA.COLUMNS
		WHERE TABLE_SCHEMA = DATABASE()
		  AND TABLE_NAME = p_table
		  AND COLUMN_NAME = p_column
	) THEN
		SET @sql_stmt = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_definition);
		PREPARE stmt FROM @sql_stmt;
		EXECUTE stmt;
		DEALLOCATE PREPARE stmt;
	END IF;
END$$
DELIMITER ;

CALL add_column_if_missing('table_product', 'ma_tra_cuu', "`ma_tra_cuu` VARCHAR(80) NOT NULL DEFAULT '' AFTER `cccd`");
CALL add_column_if_missing('table_product', 'payroll_phong_ban', "`payroll_phong_ban` VARCHAR(255) NOT NULL DEFAULT '' AFTER `hang`");
CALL add_column_if_missing('table_product', 'payroll_so_ngay_lam_viec', "`payroll_so_ngay_lam_viec` VARCHAR(50) NOT NULL DEFAULT '' AFTER `payroll_phong_ban`");
CALL add_column_if_missing('table_product', 'payroll_luong_chinh', "`payroll_luong_chinh` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_so_ngay_lam_viec`");
CALL add_column_if_missing('table_product', 'payroll_thuong_le_tet', "`payroll_thuong_le_tet` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_luong_chinh`");
CALL add_column_if_missing('table_product', 'payroll_tien_com', "`payroll_tien_com` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_thuong_le_tet`");
CALL add_column_if_missing('table_product', 'payroll_phu_cap_xang_xe', "`payroll_phu_cap_xang_xe` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_tien_com`");
CALL add_column_if_missing('table_product', 'payroll_day_lt_sat_hach', "`payroll_day_lt_sat_hach` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_phu_cap_xang_xe`");
CALL add_column_if_missing('table_product', 'payroll_chieu_sinh_tttn', "`payroll_chieu_sinh_tttn` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_day_lt_sat_hach`");
CALL add_column_if_missing('table_product', 'payroll_khac_dt_khac', "`payroll_khac_dt_khac` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_chieu_sinh_tttn`");
CALL add_column_if_missing('table_product', 'payroll_lam_them_gio', "`payroll_lam_them_gio` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_khac_dt_khac`");
CALL add_column_if_missing('table_product', 'payroll_dien_thoai', "`payroll_dien_thoai` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_lam_them_gio`");
CALL add_column_if_missing('table_product', 'payroll_tong_thu_nhap', "`payroll_tong_thu_nhap` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_dien_thoai`");
CALL add_column_if_missing('table_product', 'payroll_nld_nop_bhxh_10_5', "`payroll_nld_nop_bhxh_10_5` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_tong_thu_nhap`");
CALL add_column_if_missing('table_product', 'payroll_tt_nop_bhxh_21_5', "`payroll_tt_nop_bhxh_21_5` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_nld_nop_bhxh_10_5`");
CALL add_column_if_missing('table_product', 'payroll_thu_nhap_chiu_thue', "`payroll_thu_nhap_chiu_thue` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_tt_nop_bhxh_21_5`");
CALL add_column_if_missing('table_product', 'payroll_giam_tru_gia_canh', "`payroll_giam_tru_gia_canh` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_thu_nhap_chiu_thue`");
CALL add_column_if_missing('table_product', 'payroll_so_npt', "`payroll_so_npt` VARCHAR(40) NOT NULL DEFAULT '' AFTER `payroll_giam_tru_gia_canh`");
CALL add_column_if_missing('table_product', 'payroll_nguoi_phu_thuoc', "`payroll_nguoi_phu_thuoc` VARCHAR(255) NOT NULL DEFAULT '' AFTER `payroll_so_npt`");
CALL add_column_if_missing('table_product', 'payroll_thu_nhap_tinh_thue', "`payroll_thu_nhap_tinh_thue` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_nguoi_phu_thuoc`");
CALL add_column_if_missing('table_product', 'payroll_bac', "`payroll_bac` VARCHAR(40) NOT NULL DEFAULT '' AFTER `payroll_thu_nhap_tinh_thue`");
CALL add_column_if_missing('table_product', 'payroll_thue_tncn', "`payroll_thue_tncn` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_bac`");
CALL add_column_if_missing('table_product', 'payroll_luong_thuc_nhan', "`payroll_luong_thuc_nhan` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_thue_tncn`");
CALL add_column_if_missing('table_product', 'payroll_nghia_vu_gv', "`payroll_nghia_vu_gv` VARCHAR(120) NOT NULL DEFAULT '' AFTER `payroll_luong_thuc_nhan`");

DROP PROCEDURE IF EXISTS add_column_if_missing;

SET @index_exists = (
	SELECT COUNT(1)
	FROM INFORMATION_SCHEMA.STATISTICS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'table_product'
	  AND INDEX_NAME = 'idx_product_type_ma_tra_cuu'
);

SET @sql_stmt = IF(
	@index_exists = 0,
	'CREATE INDEX `idx_product_type_ma_tra_cuu` ON `table_product`(`type`, `ma_tra_cuu`)',
	'SELECT 1'
);

PREPARE stmt FROM @sql_stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
