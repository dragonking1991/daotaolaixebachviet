## 1. Database & Migration

- [x] 1.1 `migration_daotao.sql` (idempotent) — `table_dt_khoa`
- [x] 1.2 `table_dt_hocvien` (cầu nối ma_hv ⇄ cccd); UNIQUE(id_khoa, cccd); INDEX(ma_hv)
- [x] 1.3 `table_dt_xe` (bien_so UNIQUE, hang_xe, gv_key...)
- [x] 1.4 `table_dt_giaovien` (cccd UNIQUE, gv_key, matkhau...)
- [x] 1.5 `table_dt_lythuyet`; UNIQUE(id_khoa, cccd, mon)
- [x] 1.6 `table_dt_cabin_kq`; UNIQUE(id_khoa, ma_hv)
- [x] 1.7 `table_dt_dat_phien` (ma_phien UNIQUE)
- [x] 1.8 `table_dt_thuchanh_hinh`; UNIQUE(id_khoa, cccd)
- [x] 1.9 `table_dt_import_log`
- [x] 1.10 Bảng tự tạo idempotent qua `admin/sources/daotao/schema.php` (`dt_ensure_tables`). *(Backup + chạy migration trên DB thật khi deploy.)*

## 2. Helpers dùng chung

- [x] 2.1 `dt_mb_lower()` + `dt_norm_header()` (mảng bỏ dấu 67 cặp) — `libraries/daotao_lib.php`
- [x] 2.2 `dt_gv_key()` chuẩn hóa tên giáo viên
- [x] 2.3 `dt_norm_hang()` (đã sửa lỗi "X lên Y" lấy hạng đích)
- [x] 2.4 `dt_cccd_variants()` CCCD 11/12 số
- [x] 2.5 `dt_detect_header()` alias + fallback substring; `dt_val()`
- [x] 2.6 Hằng số ngưỡng tập trung (`dt_nguong_dat`/`dt_nguong_hinh`/`dt_nguong_lythuyet`)

## 3. Admin — Dữ liệu nền (xe & giáo viên)

- [x] 3.1 `admin/sources/daotao/xe.php`: import sheet `XE`, upsert theo biển số, chuẩn hóa hạng, gv_key
- [x] 3.2 `admin/sources/daotao/giaovien.php`: import sheet `GiaoVien`, upsert theo CCCD, mật khẩu mặc định = CCCD
- [x] 3.3 Template xe + giáo viên (list + upload)
- [x] 3.4 Ghi `table_dt_import_log`; báo lỗi từng dòng

## 4. Admin — Khóa học & học viên

- [x] 4.1 `admin/sources/daotao/khoa.php` (man/add/edit/save/delete); validate mã khóa trùng + ngày
- [x] 4.2 Import học viên (mẫu 2): bắt buộc ma_hv + cccd; báo dòng lỗi; upsert theo (cccd, id_khoa)
- [x] 4.3 Template khóa + học viên
- [x] 4.4 Menu admin (`admin/templates/layout/menu.php`)

## 5. Admin — Module Lý thuyết

- [x] 5.1 Import 6 môn, đọc "Mã đăng nhập"/"Tiến độ hoàn thành"/"Điểm kiểm tra" (gộp header nhiều dòng)
- [x] 5.2 Đạt = tiến độ > 70 AND điểm > 5; upsert (id_khoa, cccd, mon); báo dòng lỗi
- [x] 5.3 Template import + tổng hợp 6 môn

## 6. Admin — Module Cabin kết quả

- [x] 6.1 Import file cabin (dò header sâu), theo mã học viên
- [x] 6.2 Đạt = "Đáp ứng quy định"; upsert (id_khoa, ma_hv); báo dòng lỗi
- [x] 6.3 Template import + tra cứu

## 7. Admin — Module Thực hành trong hình

- [x] 7.1 Form nhập tay giờ + km; upsert (id_khoa, cccd)
- [x] 7.2 Đạt theo ngưỡng hạng (B11/B1 ≥120km & >34h; C1 ≥113km & >35h; C/CE ≥15km & >7h)
- [x] 7.3 Template danh sách + ô nhập nhanh

## 8. Admin — Module DAT

- [x] 8.1 Import DAT; chèn phiên `ma_phien` chưa có; báo số phiên trùng bỏ qua
- [x] 8.2 Tính theo phiên: gio_thuchanh, gio_dem (18h–5h), la_xe_tudong (dò biển số)
- [x] 8.3 Cộng dồn A=C+D, B, C, D, E; đánh giá theo ngưỡng hạng (B đêm ≥1)
- [x] 8.4 Template tra cứu theo khóa + tham số A/B/C/D/E
- [x] 8.5 `table_dt_import_log`; early-exit; cap dòng

## 9. Admin — Module Tổng hợp

- [x] 9.1 JOIN tính Đạt/Không đạt 4 module theo bộ lọc (ngày, CCCD, họ tên, khóa, hạng, GV)
- [x] 9.2 Template tổng hợp + kết luận đủ/chưa đủ điều kiện
- [x] 9.3 Xuất Excel Đạt/Không đạt kèm chi tiết A/B/C/D

## 10. Cổng công khai

- [x] 10.1 Tuyến `tra-cuu-qua-trinh-hoc` + `cong-giao-vien` trong `libraries/router.php`
- [x] 10.2 Cổng học viên: `ajax/tracuu_daotao.php` (chỉ đọc)
- [x] 10.3 Cổng giáo viên: đăng nhập CCCD + mật khẩu (băm), đổi mật khẩu, session `dt_gv`
- [x] 10.4 Cổng giáo viên: xem học viên phụ trách (gv_key) + cập nhật thực hành hình

## 11. Phân quyền & Kiểm thử

- [x] 11.1 Quyền `daotao_man`/`daotao_upload` trong `requick.php` + `permission_group_tpl.php`
- [x] 11.2 Lint PHP toàn bộ file mới — tất cả PASS
- [x] 11.3 Kiểm thử logic (chuẩn hóa hạng, ngưỡng LT/hình/DAT, giờ đêm) + dò header trên tiêu đề file thật (xe, GV, mẫu2, LT, cabin, DAT) — tất cả PASS. *(Import e2e trên MySQL thật nên chạy khi deploy.)*
- [ ] 11.4 Kiểm thử cổng học viên/giáo viên trên trình duyệt *(chạy khi deploy — code đã hoàn thiện, chưa dựng stack mysql+apache để e2e)*
