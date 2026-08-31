## Why

Trường cần trích chi phí xăng dầu (XD) cho giáo viên (GV) theo hóa đơn thực nộp, nhưng hiện việc đối chiếu tổng tiền hóa đơn với định mức mỗi học viên (HV) và lập bảng kê thanh toán đang làm thủ công trên Excel. Cách làm này dễ sai định mức, khó chống import trùng hóa đơn/CCCD, và không có cách để GV tự tra cứu số tiền được nhận. Cần một phân hệ trong admin để Kế toán cấu hình định mức, nạp dữ liệu hóa đơn và học viên, chạy thuật toán lọc thanh toán tự động, xuất bảng kê và khóa dữ liệu đã quyết toán; đồng thời có cổng cho GV đăng nhập bằng CCCD để tra cứu.

## What Changes

- Thêm màn cấu hình tham số thanh toán XD cho admin: định mức XD/1 HV (mặc định `3.500.000`), mức thanh toán nhóm BT (mặc định `1.200.000`), mức nhóm CK và DAT (mặc định `0`) — tất cả chỉnh sửa được.
- Admin import danh sách **Hóa đơn XD hợp lệ** theo kỳ từ Excel; chống trùng theo Mã HĐ + Ngày HĐ (và theo kỳ tổng hợp), khóa không cho import đè hóa đơn đã đưa vào quyết toán.
- Admin import danh sách **Học viên** theo nhóm (BT/CK/DAT) gắn với GV phụ trách; kiểm tra trùng **Số CCCD** trên toàn hệ thống, báo lỗi chi tiết theo dòng và **chặn lưu cả file** nếu có CCCD trùng.
- Chạy **thuật toán lọc thanh toán** tự động theo từng GV: tính tổng hóa đơn hợp lệ $S_{HĐ}$, xác định số HV tối đa $N$ thỏa $N \times \text{định mức} \le S_{HĐ}$, chọn $N$ HV chưa thanh toán đầu tiên và gán số tiền theo nhóm.
- Admin **Xuất bảng kê trích chi phí nhiên liệu** (Excel/PDF theo template); khi xuất/xác nhận, tự ghi `Ngày thanh toán = ngày thực hiện` cho các HV được trích và khóa để không kéo vào đợt lọc sau.
- Thêm **cổng tra cứu cho GV**: đăng nhập bằng CCCD, lọc theo `Từ ngày`–`Đến ngày`, hiển thị bảng hóa đơn XD và bảng học viên đã thanh toán (định mức, số tiền thực nhận, ngày thanh toán).

## Capabilities

### New Capabilities
- `fuel-cost-admin`: Quản trị chi phí xăng dầu — cấu hình định mức, import hóa đơn XD (chống trùng + khóa quyết toán), import học viên theo nhóm (chống trùng CCCD toàn hệ thống), chạy thuật toán lọc thanh toán theo GV, xuất bảng kê trích chi phí và tự cập nhật ngày thanh toán.
- `fuel-cost-teacher-portal`: Cổng tra cứu chi phí xăng dầu cho giáo viên — xác thực bằng CCCD, lọc theo khoảng thời gian, xem danh sách hóa đơn XD và danh sách học viên đã thanh toán của mình.

### Modified Capabilities
<!-- Không có spec hiện hữu trong openspec/specs/; không có yêu cầu nào của capability cũ bị thay đổi. -->

## Impact

- **Database**: thêm bảng cấu hình định mức (`table_xd_config`), bảng hóa đơn XD của GV (`table_xd_hoadon`), bảng học viên XD (`table_xd_hocvien`) và bảng đợt bảng kê (`table_xd_bangke`); ràng buộc UNIQUE cho CCCD học viên và (Mã HĐ + Ngày HĐ).
- **Admin**: thêm `admin/sources/xangdau.php` và bộ template `admin/templates/xangdau/**`; mục menu admin mới; tái dùng PHPExcel để import/xuất.
- **Frontend**: thêm cổng GV — `sources/tracuu_xangdau.php` (hoặc AJAX handler công khai) và template tra cứu; xác thực CCCD theo mẫu `ajax/tracuu.php`.
- **Config helper**: thêm `libraries/xangdau_config.php` theo mẫu `libraries/payroll_config.php` (đọc/ghi định mức, fallback mặc định).
- **Routing**: thêm tuyến công khai cho cổng tra cứu GV trong `libraries/router.php`.
- **Dependencies**: không thêm thư viện mới; tái dùng `libraries/PHPExcel`, `libraries/class/class.PDODb.php`, `libraries/class/class.Functions.php`.
