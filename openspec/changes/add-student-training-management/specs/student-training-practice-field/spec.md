## ADDED Requirements

### Requirement: Nhập tay thực hành trong hình

Hệ thống SHALL cho phép nhập tay 2 tham số cho mỗi học viên: Thời gian (giờ) và Quãng đường (km) học trong hình.

#### Scenario: Nhập tham số thực hành hình
- **WHEN** người dùng nhập thời gian và quãng đường cho một học viên rồi lưu
- **THEN** hệ thống SHALL lưu 2 tham số gắn với học viên trong khóa và ghi người nhập cùng thời điểm

#### Scenario: Cập nhật tham số đã nhập
- **WHEN** người dùng cập nhật thời gian/quãng đường cho học viên đã có dữ liệu
- **THEN** hệ thống SHALL ghi đè giá trị mới thay vì tạo bản ghi trùng

### Requirement: Đánh giá đạt thực hành trong hình theo hạng

Hệ thống SHALL xác định đạt thực hành trong hình theo ngưỡng quãng đường và thời gian của từng hạng.

#### Scenario: Đạt theo hạng B (tự động và số sàn)
- **WHEN** học viên hạng B11 hoặc B1 có quãng đường ≥ 120 km và thời gian > 34 giờ
- **THEN** hệ thống SHALL đánh dấu đạt thực hành trong hình

#### Scenario: Đạt theo hạng C1
- **WHEN** học viên hạng C1 có quãng đường ≥ 113 km và thời gian > 35 giờ
- **THEN** hệ thống SHALL đánh dấu đạt thực hành trong hình

#### Scenario: Đạt theo hạng C và CE
- **WHEN** học viên hạng C hoặc CE có quãng đường ≥ 15 km và thời gian > 7 giờ
- **THEN** hệ thống SHALL đánh dấu đạt thực hành trong hình

#### Scenario: Chưa đủ ngưỡng
- **WHEN** quãng đường hoặc thời gian chưa đạt ngưỡng của hạng
- **THEN** hệ thống SHALL đánh dấu chưa đạt thực hành trong hình

### Requirement: Đánh dấu đạt hoặc xóa kết quả hình đã chọn

Hệ thống SHALL cho phép admin chọn nhiều học viên để xác nhận đạt thực hành trong hình hoặc xóa kết quả; xác nhận đạt SHALL giữ nguyên giờ/km hiện có và SHALL được lưu như xác nhận thủ công.

#### Scenario: Xác nhận đạt thực hành hình cho học viên đã chọn
- **WHEN** admin chọn học viên và xác nhận đánh dấu đạt
- **THEN** hệ thống SHALL đặt xác nhận đạt cho các học viên đã chọn mà không ghi đè giờ/km

#### Scenario: Xóa kết quả hình đã chọn
- **WHEN** admin chọn học viên và xác nhận xóa kết quả
- **THEN** hệ thống SHALL chỉ xóa dữ liệu hình của các học viên đã chọn

#### Scenario: Theo dõi số đạt/chưa đạt
- **WHEN** admin xem trang thực hành trong hình
- **THEN** hệ thống SHALL hiển thị số học viên đạt/chưa đạt theo bộ lọc hiện tại, không để bộ lọc trạng thái làm sai số thống kê
