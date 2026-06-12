## 1. Database & Migration

- [x] 1.1 Tạo file `migration_cabin.sql` (idempotent) tạo bảng `table_cabin_khoahoc` gồm: `id`, `ten`, `ngay_batdau` (DATE), `ngay_ketthuc` (DATE), `suc_chua_ca` (INT default 3), `ngaytao`, `user_tao`, `stt`, `hienthi`
- [x] 1.2 Tạo bảng `table_cabin_dangky` gồm: `id`, `id_khoahoc`, `id_hocvien`, `cccd`, `ngay_hoc` (DATE), `ca` (INT 1–4), `gio_b_d`, `gio_kt`, `trang_thai`, `ngaytao`
- [x] 1.3 Thêm UNIQUE index `(id_khoahoc, id_hocvien, ngay_hoc, ca)` và index đếm `(id_khoahoc, ngay_hoc, ca)` trên `table_cabin_dangky`
- [x] 1.4 Thêm cột `id_cabin_khoahoc` vào `table_product` nếu chưa có (idempotent)
- [x] 1.5 Backup DB và chạy migration; xác minh schema

## 2. Cấu hình & Định tuyến

- [x] 2.1 Bổ sung loại bản ghi `cabin` vào cấu hình type (`libraries/type/`)
- [x] 2.2 Định nghĩa mảng khung giờ cố định (T2–T6: 4 ca; T7: 2 ca) dùng chung cho admin + frontend
- [x] 2.3 Thêm tuyến công khai cho trang đăng ký cabin trong `libraries/router.php`

## 3. Admin — Quản lý khóa học cabin

- [x] 3.1 Tạo `admin/sources/cabin.php` với `switch($act)`: `man`, `add`, `edit`, `save`, `delete`
- [x] 3.2 Triển khai danh sách khóa có phân trang và tìm kiếm (mẫu `get_items_kysathach`)
- [x] 3.3 Triển khai lưu khóa với validate ngày kết thúc ≥ ngày bắt đầu và sức chứa mặc định 3
- [x] 3.4 Tạo template `admin/templates/cabin/man/items_tpl.php` (bảng + modal thêm/sửa, date picker)
- [x] 3.5 Thêm mục menu admin cho khóa học cabin

## 4. Admin — Import & hiển thị học viên

- [x] 4.1 Thêm case `upload` + `uploadExcel` trong `admin/sources/cabin.php` dùng PHPExcel đọc cột A–F (STT, Họ tên, Ngày sinh, CCCD, Khóa, Người nộp hồ sơ)
- [x] 4.2 Chèn/cập nhật `table_product` theo `(cccd, type='cabin', id_cabin_khoahoc)`; tự tạo khóa mới theo tên khóa trong Excel khi chưa tồn tại; báo lỗi nếu file rỗng/không đọc được
- [x] 4.3 Tạo template `admin/templates/cabin/upload/items_tpl.php` (ô tải file + hướng dẫn import đa khóa)
- [x] 4.4 Thêm case `data` + `ajaxData` hiển thị đầy đủ thông tin học viên của khóa
- [x] 4.5 Tạo template `admin/templates/cabin/data/items_tpl.php`

## 5. Admin — Xem & xuất đăng ký

- [x] 5.1 Thêm case xem danh sách đăng ký theo ngày/ca của một khóa
- [x] 5.2 Thêm case `exportExcel` dùng PHPExcel xuất Họ tên, CCCD, Hạng, Ngày học, Ca, Giờ
- [x] 5.3 Tạo template hiển thị đăng ký + nút xuất Excel

## 6. Frontend — Xác thực CCCD & lưới đăng ký

- [x] 6.1 Tạo `sources/dangky_cabin.php` nạp danh sách khóa cabin đang hiển thị
- [x] 6.2 Tạo template `templates/cabin/cabin_tpl.php`: ô nhập CCCD, lưới ca theo tuần, ghi chú hướng dẫn
- [x] 6.3 Tạo AJAX tra cứu CCCD (chuẩn hóa 11/12 số) trả về UI đăng ký cho đúng học viên; báo không tìm thấy nếu không khớp
- [x] 6.4 Render lưới: T2–T6 đủ 4 ca, T7 chỉ Ca 1–2, loại Chủ nhật và ngày ngoài khoảng khóa
- [x] 6.5 Hiển thị trạng thái block cho ca đã đạt sức chứa và ca học viên đã đăng ký

## 7. Frontend — Chốt đăng ký (ghi dữ liệu)

- [x] 7.1 Tạo AJAX handler đăng ký ca: chạy trong transaction, `SELECT ... FOR UPDATE` đếm đăng ký dùng chung cabin theo `(ngày, ca)`
- [x] 7.2 Từ chối nếu: ca đầy, học viên đã đặt ca đó, ngoài khung giờ hợp lệ, hoặc quá ngày kết thúc đăng ký (kiểm tra phía server)
- [x] 7.3 Chèn bản ghi đăng ký và commit; trả kết quả cập nhật lưới
- [x] 7.4 Khi quá thời gian kết thúc: chuyển giao diện sang chỉ-đọc và hiển thị hướng dẫn liên hệ văn phòng
- [x] 7.5 Cho phép mỗi học viên giữ tối đa 3 ca trong cùng 1 khóa; hiển thị UI `Đăng ký thêm` khi chưa chạm giới hạn và chặn khi đã đủ 3 ca
- [x] 7.6 Cho phép học viên hủy một ca chưa diễn ra để giải phóng chỗ và đăng ký ca khác

## 8. Kiểm thử & Triển khai

- [ ] 8.1 Smoke test: tạo khóa → import Excel → đăng ký bằng CCCD → kiểm tra block khi đầy → xuất Excel
- [x] 8.2 Test biên: CCCD 11/12 số, Thứ 7, Chủ nhật, ngày ngoài khoảng khóa, sau ngày kết thúc
- [x] 8.3 Test đua điều kiện: hai đăng ký đồng thời ô ca cuối cùng chỉ chấp nhận đúng sức chứa
- [x] 8.4 Xác minh route, menu admin và quyền truy cập; cập nhật ghi chú repo nếu cần
