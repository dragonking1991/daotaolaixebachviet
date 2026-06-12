-- Backfill script for payroll employee import v2
-- Safe to run multiple times.

-- 1) Generate ma_tra_cuu for old employee rows if missing.
UPDATE table_product
SET ma_tra_cuu = CONCAT('NV-', id)
WHERE type = 'nhan-vien'
  AND (ma_tra_cuu IS NULL OR ma_tra_cuu = '');

-- 2) Backfill core payroll columns from options2 JSON (only when destination is empty).
UPDATE table_product
SET
  payroll_so_ngay_lam_viec = IF(payroll_so_ngay_lam_viec = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.songaylamviec')), ''), payroll_so_ngay_lam_viec),
  payroll_luong_chinh = IF(payroll_luong_chinh = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.luongchinh')), ''), payroll_luong_chinh),
  payroll_thuong_le_tet = IF(payroll_thuong_le_tet = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.thuongletet')), ''), payroll_thuong_le_tet),
  payroll_tien_com = IF(payroll_tien_com = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.tiencom')), ''), payroll_tien_com),
  payroll_phu_cap_xang_xe = IF(payroll_phu_cap_xang_xe = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.phucapxangxe')), ''), payroll_phu_cap_xang_xe),
  payroll_day_lt_sat_hach = IF(payroll_day_lt_sat_hach = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.dayltsathach')), ''), payroll_day_lt_sat_hach),
  payroll_chieu_sinh_tttn = IF(payroll_chieu_sinh_tttn = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.chieusinhtttn')), ''), payroll_chieu_sinh_tttn),
  payroll_khac_dt_khac = IF(payroll_khac_dt_khac = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.khacdtkhac')), ''), payroll_khac_dt_khac),
  payroll_lam_them_gio = IF(payroll_lam_them_gio = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.lamthemgio')), ''), payroll_lam_them_gio),
  payroll_dien_thoai = IF(payroll_dien_thoai = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.dienthoai')), JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.ienthoai')), ''), payroll_dien_thoai),
  payroll_tong_thu_nhap = IF(payroll_tong_thu_nhap = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.tongthunhap')), ''), payroll_tong_thu_nhap),
  payroll_nld_nop_bhxh_10_5 = IF(payroll_nld_nop_bhxh_10_5 = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.nldnopbhxh105')), ''), payroll_nld_nop_bhxh_10_5),
  payroll_tt_nop_bhxh_21_5 = IF(payroll_tt_nop_bhxh_21_5 = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.ttnopbhxh215')), ''), payroll_tt_nop_bhxh_21_5),
  payroll_thu_nhap_chiu_thue = IF(payroll_thu_nhap_chiu_thue = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.thunhapchiuthue')), ''), payroll_thu_nhap_chiu_thue),
  payroll_giam_tru_gia_canh = IF(payroll_giam_tru_gia_canh = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.giamtrugiacanh')), ''), payroll_giam_tru_gia_canh),
  payroll_so_npt = IF(payroll_so_npt = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.sonpt')), ''), payroll_so_npt),
  payroll_nguoi_phu_thuoc = IF(payroll_nguoi_phu_thuoc = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.nguoiphuthuoc')), ''), payroll_nguoi_phu_thuoc),
  payroll_thu_nhap_tinh_thue = IF(payroll_thu_nhap_tinh_thue = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.thunhaptinhthue')), ''), payroll_thu_nhap_tinh_thue),
  payroll_bac = IF(payroll_bac = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.bac')), ''), payroll_bac),
  payroll_thue_tncn = IF(payroll_thue_tncn = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.thuetncn')), ''), payroll_thue_tncn),
  payroll_luong_thuc_nhan = IF(payroll_luong_thuc_nhan = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.luongthucnhan')), JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.luongthycnhan')), ''), payroll_luong_thuc_nhan),
  payroll_nghia_vu_gv = IF(payroll_nghia_vu_gv = '', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.detail.nghiavugv')), ''), payroll_nghia_vu_gv)
WHERE type = 'nhan-vien';
