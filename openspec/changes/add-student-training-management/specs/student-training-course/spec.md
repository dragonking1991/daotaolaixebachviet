## ADDED Requirements

### Requirement: Quản lý khóa học đào tạo

Hệ thống SHALL cho phép admin tạo, sửa, xóa khóa học với 5 thông tin: hạng, tên khóa, mã khóa học, ngày khai giảng, ngày mãn khóa.

#### Scenario: Tạo khóa học hợp lệ
- **WHEN** admin nhập hạng, tên khóa, mã khóa học, ngày khai giảng, ngày mãn khóa rồi lưu
- **THEN** hệ thống SHALL tạo khóa mới với cờ hiển thị bật và lưu người tạo cùng thời điểm tạo

#### Scenario: Mã khóa học trùng
- **WHEN** admin lưu khóa có mã khóa học đã tồn tại
- **THEN** hệ thống SHALL từ chối và báo lỗi trùng mã khóa

#### Scenario: Ngày mãn khóa trước ngày khai giảng
- **WHEN** admin lưu khóa với ngày mãn khóa nhỏ hơn ngày khai giảng
- **THEN** hệ thống SHALL từ chối lưu và báo lỗi rõ ràng

### Requirement: Import danh sách học viên và lập ánh xạ mã học viên ⇄ CCCD

Hệ thống SHALL cho phép admin nạp danh sách học viên của một khóa từ file `.xlsx` theo mẫu 2, trong đó mỗi dòng chứa cả mã học viên trung tâm và CCCD.

#### Scenario: Import danh sách học viên hợp lệ
- **WHEN** admin tải lên file có mã học viên, CCCD, họ tên, ngày sinh, giáo viên, người giới thiệu, hệ đào tạo
- **THEN** hệ thống SHALL tạo hoặc cập nhật học viên trong khóa và lưu đồng thời `ma_hv` và `cccd` làm bảng ánh xạ dùng chung

#### Scenario: Dòng thiếu mã học viên hoặc CCCD
- **WHEN** một dòng thiếu mã học viên hoặc thiếu CCCD
- **THEN** hệ thống SHALL báo dòng lỗi cụ thể và không ghi học viên đó, vì thiếu khóa nối sẽ khiến không gom được kết quả các module

#### Scenario: Học viên đã tồn tại trong khóa
- **WHEN** một dòng có CCCD đã tồn tại trong cùng khóa
- **THEN** hệ thống SHALL cập nhật thông tin học viên thay vì tạo bản ghi trùng

### Requirement: Xem danh sách học viên theo khóa

Hệ thống SHALL cho phép admin xem danh sách học viên của một khóa với đầy đủ thông tin đã import.

#### Scenario: Xem danh sách học viên
- **WHEN** admin mở danh sách học viên của một khóa
- **THEN** hệ thống SHALL hiển thị mã học viên, họ tên, ngày sinh, CCCD, hạng, giáo viên, người giới thiệu của từng học viên
