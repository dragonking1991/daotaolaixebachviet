# Proposal: Payroll Employee Import V2

## Why

Import `nhan-vien` hiện không hoạt động đúng với file lương thực tế vì header thật nằm ở dòng 12, không phải dòng 1. Ngoài ra dữ liệu payroll đang được lưu chủ yếu trong `options2`, chưa có cột DB riêng để tra cứu/lọc/báo cáo ổn định.

Nhu cầu mới:

1. Nhận diện header theo hướng linh hoạt (Option B) nhưng phù hợp thực tế hiện tại là header ở dòng 12.
2. Cho phép import khi không có CCCD, và có field tham chiếu riêng để tra cứu/cập nhật sau.
3. Lưu toàn bộ cột payroll thành field riêng trong DB.
4. Dòng aggregate (ví dụ bộ phận/phòng ban) được giữ làm ngữ cảnh và gán cho nhân viên bên dưới.
5. Public tra cứu chỉ dùng `ma_tra_cuu`.
6. Khi admin cập nhật `cccd` cho nhân viên, hệ thống tự đồng bộ `ma_tra_cuu = cccd`.
7. Khi import có cột `cccd`, hệ thống ưu tiên lấy `cccd` làm `ma_tra_cuu`.

## Proposed Change

### 1) Header Detection (Option B)

- Quét trong phạm vi dòng 1..20 để tìm dòng header có số lượng match alias cao nhất.
- Với file hiện tại, dòng 12 sẽ được chọn tự động.
- Dữ liệu bắt đầu từ `header_row + 1`.

### 2) Employee Identity Khi Chưa Có CCCD

- CCCD không còn là điều kiện bắt buộc để import.
- Thêm field tham chiếu mới trong DB (ví dụ `ma_tra_cuu`) để dùng cho tra cứu tạm thời trước khi có CCCD.
- Field này cho phép chỉnh sửa trong admin/public flow theo rule nghiệp vụ.
- Public endpoint chỉ tra cứu theo `ma_tra_cuu`.
- Nếu `cccd` có dữ liệu (khi import hoặc khi admin chỉnh sửa), đồng bộ `ma_tra_cuu` theo `cccd`.

### 3) Store Payroll Columns In Dedicated DB Fields

Tách các cột sau thành field riêng trong DB (không chỉ JSON):

- Họ và tên
- Chức vụ
- Số ngày làm việc
- Lương chính
- Thưởng lễ tết
- Tiền cơm
- Phụ cấp xăng xe
- Dạy LT Sát hạch
- Chiêu sinh TTTN
- Khác (DT - Khác)
- Làm thêm giờ
- Điện thoại
- Tổng thu nhập
- NLD Nộp BHXH 10.5%
- TT Nộp BHXH 21.5%
- Thu nhập chịu thuế
- Giảm trừ gia cảnh
- Số NPT
- Người phụ thuộc
- Thu nhập tính thuế
- Bậc
- Thuế TNCN
- Lương thực nhận
- Nghĩa vụ GV

Vẫn lưu `options2` như bản ghi nguồn để trace/debug.

### 4) Aggregate Department Row Handling

- Nhận diện dòng aggregate là dòng bộ phận/phòng ban (không phải nhân viên).
- Cập nhật `current_department` từ dòng này.
- Gán `current_department` cho các nhân viên kế tiếp tới khi gặp bộ phận mới.
- Có field DB riêng để lưu phòng ban payroll phục vụ tra cứu.

## Scope

### In Scope

1. Thêm migration DB cho các field mới (tham chiếu tra cứu + payroll + phòng ban payroll).
2. Cập nhật import `nhan-vien` trong `admin/sources/import.php` theo Option B.
3. Cập nhật rule insert/update để không phụ thuộc CCCD khi import từ payroll.
4. Đồng bộ dữ liệu tối thiểu với các field cũ đang dùng trong giao diện hiện tại (`tenvi`, `khoa`, `hang`, `cccd`).

### Out of Scope

1. Không triển khai tính lương tự động.
2. Không làm versioning nhiều kỳ lương trong cùng change này.
3. Không refactor toàn bộ màn admin ngoài phần cần để hiển thị/chỉnh sửa field mới.

## Success Criteria

1. Import file payroll mẫu thành công, không còn trường hợp báo success nhưng insert 0 dòng do sai header row.
2. Mỗi nhân viên được lưu đủ dữ liệu payroll trong cột DB riêng.
3. Nhân viên chưa có CCCD vẫn được import và tra cứu qua field tham chiếu mới.
4. Dòng aggregate được map thành thông tin phòng ban cho đúng nhóm nhân viên.
