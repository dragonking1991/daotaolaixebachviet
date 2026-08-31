## 1. Database & Migration

- [x] 1.1 Thêm cột `thong_tin_ban_hang VARCHAR(255)` và `chi_tiet VARCHAR(50)` vào `table_xd_hoadon` (idempotent) trong `xd_ensure_tables()` và `migration_xangdau.sql`
- [x] 1.2 Xác minh các cột đã có từ change trước còn nguyên: `bien_so`, `gv_key` (hóa đơn); `khoa`, `nguoi_nop`, `gv_key`, `ngaysinh` (học viên)

## 2. Nhận diện cột linh hoạt (cả 2 file)

- [x] 2.1 Bổ sung alias học viên: teacher `phanxe|giaovien|gv|gvphutrach`; nhóm `nhom|ghichu`; `nguoinop`, `khoa`, `hovaten`, `socccd*`, `ngaythangnamsinh`
- [x] 2.2 Bổ sung alias hóa đơn: `sohoadon`, `ngay`, `thongtinbanhang`, `chitiet`, `sotienhd`, `bienso`, `gv`
- [x] 2.3 Thêm nhận diện cột **nhóm theo giá trị**: nếu chưa map `nhom`, chọn cột có tỉ lệ ô thuộc {BT,CK,DAT} cao nhất trong vùng dữ liệu
- [x] 2.4 Lưu `thong_tin_ban_hang`, `chi_tiet` khi import hóa đơn; lưu `nguoi_nop`, `khoa` khi import học viên (đảm bảo hiển thị đủ)

## 3. Hỗ trợ import .xlsb

- [x] 3.1 Chấp nhận ext `xlsb` ở cả `xd_upload_hoadon_excel` và `xd_upload_hocvien_excel`
- [x] 3.2 Hàm `xd_convert_to_xlsx($path)`: dò công cụ (`soffice --headless --convert-to xlsx` → `ssconvert` → `python3 pyxlsb`), chuyển sang `.xlsx` tạm; trả về đường dẫn hoặc false
- [x] 3.3 Khi ext=`xlsb`: chuyển đổi rồi nạp bằng `PHPExcel_IOFactory::createReader('Excel2007')`; nếu không có công cụ → `transfer()` báo lỗi hướng dẫn lưu `.xlsx`
- [x] 3.4 Chọn đúng sheet theo tên chuẩn hóa chứa `hoc vien`/`học viên` (fallback sheet đầu); đặt timeout/memory; xóa file tạm sau khi đọc
- [ ] 3.5 Ghi chú triển khai: cách cài LibreOffice trên máy chủ (README/Docker) để bật luồng `.xlsb`

## 4. Bảng kê trích chi phí nhiên liệu (đúng mẫu)

- [x] 4.1 Viết `xd_export_bangke_excel` mới: mỗi giáo viên một sheet; tiêu đề trung tâm (từ `#_setting.tenvi`), "Giáo viên", "Ngày quyết toán", số bảng kê (từ `id` `table_xd_bangke`)
- [x] 4.2 Bảng **Nội dung** (hóa đơn của GV trong đợt): STT, Số hóa đơn, Ngày, Thông tin bán hàng, Chi tiết, Số tiền HĐ, Biển số xe + dòng Tổng cộng
- [x] 4.3 Bảng **Danh sách học viên** (HV được trích của GV): STT, Khóa, Họ tên, CCCD/CC, Năm sinh, Định mức, Số tiền thanh toán, Nhóm + dòng Tổng cộng
- [x] 4.4 Dòng chữ ký: Phòng Đào tạo | Kế Toán | Giáo viên quyết toán; định dạng border/merge cho giống mẫu
- [x] 4.5 Trong `xd_xuat_bangke`: thu thập danh sách hóa đơn theo từng GV (đã gộp vào đợt) để đưa vào bảng kê; giữ logic ghi `ngay_thanh_toan` + khóa hóa đơn

## 5. Hiển thị admin

- [x] 5.1 Danh sách học viên: hiển thị thêm Người nộp (nếu cần) và đảm bảo Khóa/Giáo viên hiển thị đúng
- [x] 5.2 Danh sách hóa đơn: hiển thị Thông tin bán hàng, Chi tiết, Biển số, Giáo viên
- [x] 5.3 Cập nhật ghi chú cột trên trang import cho đúng biến thể thật (PHÂN XE, GHI CHÚ, hỗ trợ .xlsb)

## 6. Cổng GV (xác nhận)

- [x] 6.1 Kiểm tra `ajax/tracuu_xangdau.php`: CCCD → hồ sơ nhân viên → tên → `gv_key` → dữ liệu XD; báo không tìm thấy đúng cách
- [x] 6.2 Đảm bảo bảng hóa đơn và bảng học viên đã thanh toán hiển thị đúng theo khoảng ngày

## 7. Kiểm thử & Triển khai

- [x] 7.1 Test import file thật `TỔNG HỢP HÓA ĐƠN XĂNG DẦU TH05-06.xlsx`: đủ cột, tiền/ngày đúng, GV theo tên
- [x] 7.2 Test import file thật `THEO DÕI XĂNG DẦU.xlsb`: chuyển đổi (nếu có công cụ) hoặc báo lỗi rõ; cột PHÂN XE→GV, GHI CHÚ→nhóm
- [ ] 7.3 Test chạy lọc rồi xuất bảng kê: đối chiếu bố cục với ảnh mẫu (Cao Đình Bắc: bảng HĐ + danh sách HV + tổng cộng)
- [ ] 7.4 Test cổng GV bằng CCCD của một giáo viên có trong hồ sơ nhân viên
- [x] 7.5 Lint PHP (`php -l`) và xác minh route/menu/quyền
