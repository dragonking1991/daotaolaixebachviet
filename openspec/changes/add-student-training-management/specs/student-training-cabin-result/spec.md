## ADDED Requirements

### Requirement: Import kết quả cabin từ Excel

Hệ thống SHALL cho phép admin nạp kết quả cabin từ file `.xlsx` theo mẫu (dạng `cabin - 13C1`) theo mã học viên. Module này độc lập hoàn toàn với phân hệ đăng ký lịch học cabin hiện có.

#### Scenario: Import file cabin hợp lệ
- **WHEN** admin tải lên file cabin có mã khóa học, hạng, và bảng học viên gồm họ tên, mã học viên, tổng thời gian, tổng số nội dung, ghi chú kết quả
- **THEN** hệ thống SHALL ghi kết quả cabin cho từng học viên khớp theo mã học viên trong khóa

#### Scenario: Đánh giá đạt cabin
- **WHEN** dòng học viên có kết quả "Đáp ứng quy định"
- **THEN** hệ thống SHALL đánh dấu học viên đạt cabin; ngược lại đánh dấu không đạt

#### Scenario: Học viên trong file không thuộc khóa
- **WHEN** một dòng có mã học viên không khớp học viên nào trong khóa
- **THEN** hệ thống SHALL báo dòng lỗi cụ thể và bỏ qua dòng đó

#### Scenario: Re-import kết quả cabin
- **WHEN** admin import lại file cabin của khóa đã có kết quả
- **THEN** hệ thống SHALL cập nhật kết quả theo mã học viên thay vì tạo bản ghi trùng

### Requirement: Tra cứu kết quả cabin

Hệ thống SHALL cho phép tra cứu kết quả cabin theo học viên/CCCD/khóa.

#### Scenario: Tra cứu cabin
- **WHEN** admin tra cứu theo học viên/CCCD/khóa
- **THEN** hệ thống SHALL hiển thị tổng thời gian, số nội dung và kết luận đạt/không đạt cabin
