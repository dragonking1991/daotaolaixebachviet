## ADDED Requirements

### Requirement: Admin tạo và quản lý khóa học cabin

Hệ thống SHALL cho phép admin tạo, sửa, xóa khóa học cabin với tên khóa, ngày bắt đầu, ngày kết thúc đăng ký và sức chứa mỗi ca.

#### Scenario: Tạo khóa học cabin hợp lệ
- **WHEN** admin nhập tên khóa, ngày bắt đầu, ngày kết thúc và sức chứa mỗi ca rồi lưu
- **THEN** hệ thống SHALL tạo một khóa học cabin mới với cờ hiển thị bật và lưu thông tin người tạo cùng thời điểm tạo

#### Scenario: Sức chứa mỗi ca mặc định
- **WHEN** admin tạo khóa mà không nhập sức chứa mỗi ca
- **THEN** hệ thống SHALL gán sức chứa mặc định là 3 lịch cho mỗi ca

#### Scenario: Ngày kết thúc trước ngày bắt đầu
- **WHEN** admin lưu khóa với ngày kết thúc nhỏ hơn ngày bắt đầu
- **THEN** hệ thống SHALL từ chối lưu và hiển thị thông báo lỗi rõ ràng

#### Scenario: Xóa khóa học cabin
- **WHEN** admin xóa một khóa học cabin
- **THEN** hệ thống SHALL ẩn/xóa khóa và không còn hiển thị khóa đó cho học viên đăng ký

### Requirement: Admin import danh sách học viên từ Excel

Hệ thống SHALL cho phép admin nạp danh sách học viên cabin từ file `.xlsx` hoặc `.xls` theo mẫu cột cố định, trong đó mỗi dòng có thể thuộc một khóa khác nhau.

#### Scenario: Import file hợp lệ
- **WHEN** admin tải lên file Excel có các cột STT, Họ tên, Ngày sinh, CCCD, Khóa, Người nộp hồ sơ từ dòng 2 trở đi
- **THEN** hệ thống SHALL tạo hoặc cập nhật bản ghi học viên type `cabin` gắn đúng khóa ghi trong từng dòng theo CCCD

#### Scenario: Import học viên đã tồn tại trong khóa
- **WHEN** một dòng có CCCD đã tồn tại trong cùng khóa
- **THEN** hệ thống SHALL cập nhật thông tin học viên thay vì tạo bản ghi trùng

#### Scenario: Import phát sinh khóa mới từ Excel
- **WHEN** một dòng Excel chứa tên khóa chưa tồn tại trong hệ thống
- **THEN** hệ thống SHALL tự tạo khóa cabin mới với ngày bắt đầu và ngày kết thúc tạm thời, đồng thời cảnh báo admin cần cập nhật thời gian cụ thể sau khi import

#### Scenario: File không đọc được hoặc rỗng
- **WHEN** hệ thống đọc file Excel và không có dòng dữ liệu hợp lệ nào
- **THEN** hệ thống SHALL từ chối import và báo lỗi rõ ràng cho admin

### Requirement: Admin xem chi tiết học viên và lịch đã đăng ký

Hệ thống SHALL cho phép admin xem đầy đủ thông tin học viên đã import và các ca học đã được đăng ký theo từng khóa.

#### Scenario: Xem danh sách học viên của khóa
- **WHEN** admin mở danh sách học viên của một khóa
- **THEN** hệ thống SHALL hiển thị các mục Họ tên, Ngày sinh, CCCD, Người nộp hồ sơ của từng học viên

#### Scenario: Xem các đăng ký theo ngày và ca
- **WHEN** admin mở danh sách đăng ký của một khóa
- **THEN** hệ thống SHALL hiển thị từng đăng ký gồm học viên, ngày học, ca và khung giờ

### Requirement: Admin xuất danh sách đăng ký ra Excel

Hệ thống SHALL cho phép admin xuất danh sách đăng ký của một khóa ra file Excel để theo dõi.

#### Scenario: Xuất Excel đăng ký
- **WHEN** admin chọn xuất Excel cho một khóa
- **THEN** hệ thống SHALL tạo file `.xlsx` gồm Họ tên, CCCD, Người nộp hồ sơ, Ngày học, Ca và khung giờ của các đăng ký
