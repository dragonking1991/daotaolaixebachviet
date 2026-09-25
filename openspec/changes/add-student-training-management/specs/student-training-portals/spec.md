## ADDED Requirements

### Requirement: Cổng tra cứu công khai cho học viên

Hệ thống SHALL cung cấp trang công khai cho phép học viên nhập CCCD để tự xem tiến độ đào tạo của mình.

#### Scenario: Tra cứu bằng CCCD hợp lệ
- **WHEN** học viên nhập CCCD (chuẩn hóa 11/12 số) khớp với một học viên
- **THEN** hệ thống SHALL hiển thị trạng thái 4 module (Lý thuyết, Cabin, Thực hành trong hình, DAT) và kết luận đủ/chưa đủ điều kiện kiểm tra, ở chế độ chỉ đọc

#### Scenario: CCCD không khớp
- **WHEN** CCCD nhập vào không khớp học viên nào
- **THEN** hệ thống SHALL báo không tìm thấy, không lộ thông tin học viên khác

### Requirement: Cổng đăng nhập giáo viên

Hệ thống SHALL cung cấp cổng cho giáo viên đăng nhập bằng CCCD và mật khẩu, với khả năng đổi mật khẩu.

#### Scenario: Đăng nhập hợp lệ
- **WHEN** giáo viên nhập CCCD và mật khẩu đúng
- **THEN** hệ thống SHALL cho vào cổng giáo viên và hiển thị học viên do giáo viên đó phụ trách (nối theo tên chuẩn hóa)

#### Scenario: Đổi mật khẩu
- **WHEN** giáo viên đã đăng nhập yêu cầu đổi mật khẩu và nhập mật khẩu mới hợp lệ
- **THEN** hệ thống SHALL lưu mật khẩu mới dạng băm và yêu cầu dùng mật khẩu mới cho lần đăng nhập sau

#### Scenario: Đăng nhập sai
- **WHEN** CCCD hoặc mật khẩu không đúng
- **THEN** hệ thống SHALL từ chối đăng nhập và báo lỗi chung, không tiết lộ trường nào sai

### Requirement: Giáo viên xem và cập nhật học viên phụ trách

Hệ thống SHALL cho phép giáo viên đã đăng nhập xem tiến độ và cập nhật dữ liệu thực hành trong hình cho học viên mình phụ trách.

#### Scenario: Xem học viên phụ trách
- **WHEN** giáo viên mở danh sách học viên của mình
- **THEN** hệ thống SHALL hiển thị trạng thái 4 module của từng học viên, nêu rõ phần còn thiếu

#### Scenario: Cập nhật thực hành trong hình
- **WHEN** giáo viên nhập thời gian và quãng đường thực hành trong hình cho học viên phụ trách
- **THEN** hệ thống SHALL lưu giá trị và tính lại trạng thái đạt của module thực hành trong hình
