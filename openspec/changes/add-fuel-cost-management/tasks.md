## 1. Database & Migration

- [x] 1.1 Tạo `migration_xangdau.sql` (idempotent) tạo `table_xd_config` (key/value) theo mẫu `table_payroll_config`
- [x] 1.2 Tạo `table_xd_hoadon`: `id`, `gv_cccd`, `gv_hoten`, `ma_hoa_don`, `ngay_hoa_don` (DATE), `tong_tien` (DECIMAL(18,2)), `ky`, `da_quyettoan` (TINYINT), `id_bangke`, `ngaytao`, `user_tao`
- [x] 1.3 Tạo `table_xd_hocvien`: `id`, `ho_ten`, `cccd`, `ngaysinh`, `nhom` (BT/CK/DAT), `gv_cccd`, `gv_hoten`, `dinh_muc`, `so_tien_thanh_toan`, `ngay_thanh_toan` (DATE NULL), `id_bangke`, `ngaytao`, `user_tao`
- [x] 1.4 Tạo `table_xd_bangke`: `id`, `ngay_lap` (DATE), `ky`, `tong_hocvien`, `tong_tien`, `user_tao`, `ngaytao`
- [x] 1.5 Thêm UNIQUE `(cccd)` trên `table_xd_hocvien`; UNIQUE `(ma_hoa_don, ngay_hoa_don)` trên `table_xd_hoadon`; index theo `gv_cccd`, `ngay_thanh_toan`, `ngay_hoa_don`, `ky`
- [ ] 1.6 Backup DB và chạy migration; xác minh schema

## 2. Cấu hình định mức (config helper + admin)

- [x] 2.1 Tạo `libraries/xangdau_config.php` với `getXdConfig` / `saveXdConfig` (mặc định `xd_dinh_muc=3500000`, `xd_muc_bt=1200000`, `xd_muc_ck=0`, `xd_muc_dat=0`), fallback khi bảng chưa có (theo mẫu `payroll_config.php`)
- [x] 2.2 Thêm case cấu hình trong `admin/sources/xangdau.php` (`config`, `saveConfig`) chuẩn hóa số (bỏ dấu chấm/ngàn, chặn số âm)
- [x] 2.3 Tạo template `admin/templates/xangdau/config/item_edit_tpl.php` (form nhập định mức, mức BT, mức CK/DAT)

## 3. Admin — Import hóa đơn XD

- [x] 3.1 Tạo `admin/sources/xangdau.php` với `switch($act)` + `xd_ensure_tables()` (tạo bảng nếu thiếu, theo mẫu `hoadon.php`) + kiểm tra quyền
- [x] 3.2 Case `uploadHoadon` + `uploadHoadonExcel` dùng PHPExcel đọc cột Mã HĐ, Ngày HĐ, Tổng tiền, CCCD GV, Tên GV, Kỳ; chuẩn hóa header tiếng Việt không dấu
- [x] 3.3 Chống trùng: UNIQUE `(ma_hoa_don, ngay_hoa_don)`, chặn import đè kỳ đã tồn tại, không đè hóa đơn `da_quyettoan=1`; báo lỗi rõ và báo file rỗng
- [x] 3.4 Case danh sách hóa đơn có phân trang + tìm kiếm; template `admin/templates/xangdau/hoadon/items_tpl.php` và `.../uploadHoadon/items_tpl.php`

## 4. Admin — Import học viên (chống trùng CCCD)

- [x] 4.1 Case `uploadHocvien` + `uploadHocvienExcel` đọc cột Họ tên, Ngày sinh, CCCD, Nhóm, CCCD GV, Tên GV
- [x] 4.2 Kiểm tra toàn bộ file trước khi ghi: phát hiện CCCD trùng trong file và với DB; nếu có bất kỳ trùng nào → không ghi dòng nào, trả danh sách lỗi theo dòng (ví dụ `Dòng 15: CCCD ... đã tồn tại`)
- [x] 4.3 Validate nhóm chỉ nhận BT/CK/DAT; báo lỗi dòng sai nhóm và chặn lưu file
- [x] 4.4 Ghi học viên theo giao dịch all-or-nothing; báo lỗi nếu file rỗng
- [x] 4.5 Case danh sách học viên + phân trang; template `admin/templates/xangdau/hocvien/items_tpl.php` và `.../uploadHocvien/items_tpl.php`

## 5. Admin — Thuật toán lọc thanh toán

- [x] 5.1 Case `loc` (preview): với từng `gv_cccd`, tính $S_{HĐ}$ từ hóa đơn hợp lệ chưa quyết toán trong kỳ/khoảng
- [x] 5.2 Tính $N = \lfloor S_{HĐ} / \text{định mức} \rfloor$; chọn $N$ học viên chưa thanh toán đầu tiên (sắp theo `id`)
- [x] 5.3 Gán số tiền theo nhóm (BT = mức BT, CK/DAT = mức tương ứng) và `dinh_muc` hiện hành; hiển thị bảng preview
- [x] 5.4 Template `admin/templates/xangdau/loc/items_tpl.php` (chọn kỳ/khoảng ngày, nút chạy lọc, bảng kết quả, nút xuất bảng kê)

## 6. Admin — Xuất bảng kê & quyết toán

- [x] 6.1 Case `xuatBangKe`: trong giao dịch tạo `table_xd_bangke`, ghi `ngay_thanh_toan = ngày thực hiện`, `id_bangke`, chốt `dinh_muc`/`so_tien_thanh_toan` cho HV được trích
- [x] 6.2 Đánh dấu hóa đơn liên quan `da_quyettoan=1`, `id_bangke`
- [x] 6.3 Sinh file Excel bảng kê bằng PHPExcel (`Excel2007`): STT, Họ tên, CCCD, Nhóm, GV, Định mức, Số tiền, Ngày TT (theo template mẫu)
- [x] 6.4 Đảm bảo HV đã có `ngay_thanh_toan` bị loại khỏi lần lọc sau

## 7. Menu & quyền admin

- [x] 7.1 Thêm mục menu admin cho phân hệ XD (cấu hình, hóa đơn, học viên, lọc thanh toán)
- [x] 7.2 Khai báo và kiểm tra quyền truy cập cho từng case (theo mẫu `hoadon_permission_denied`)

## 8. Frontend — Cổng tra cứu Giáo viên

- [x] 8.1 Tạo `sources/tracuu_xangdau.php` (hoặc AJAX handler công khai) + route công khai trong `libraries/router.php`
- [x] 8.2 Template cổng GV: ô nhập CCCD, bộ lọc `Từ ngày`–`Đến ngày`
- [x] 8.3 Xác thực CCCD (chuẩn hóa biến thể 11/12 số như `ajax/tracuu.php`); báo không tìm thấy nếu không khớp
- [x] 8.4 Bảng hóa đơn XD theo `gv_cccd` và `ngay_hoa_don` trong khoảng (Ngày HĐ, Số HĐ, Tiền HĐ)
- [x] 8.5 Bảng học viên đã thanh toán theo `gv_cccd` và `ngay_thanh_toan` trong khoảng (Họ tên, CCCD, Nhóm, Định mức, Số tiền, Ngày TT)

## 9. Kiểm thử & Triển khai

- [ ] 9.1 Smoke test: cấu hình định mức → import hóa đơn → import học viên → chạy lọc → xuất bảng kê → GV tra cứu bằng CCCD
- [ ] 9.2 Test chống trùng: CCCD trùng trong file và với DB đều bị chặn cả file với lỗi theo dòng; import đè kỳ/hóa đơn đã quyết toán bị chặn
- [ ] 9.3 Test thuật toán theo ví dụ nghiệp vụ ($S_{HĐ}=13.5M$, định mức 3.5M → 3 HV) và biên (S_HĐ nhỏ hơn định mức → 0 HV)
- [ ] 9.4 Test auto cập nhật ngày TT và loại HV đã thanh toán khỏi đợt lọc sau
- [ ] 9.5 Xác minh route, menu admin, quyền truy cập; cập nhật ghi chú repo nếu cần
