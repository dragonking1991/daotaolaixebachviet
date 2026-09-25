## ADDED Requirements

### Requirement: Import kết quả từng môn lý thuyết

Hệ thống SHALL cho phép admin nạp kết quả 6 môn lý thuyết (Cấu tạo sửa chữa, Kỹ thuật lái xe, Phần 1, Phần 2, Phần 3, Đạo đức người lái xe), mỗi môn một file `.xlsx`, đọc cột theo tên tiêu đề.

#### Scenario: Import file một môn hợp lệ
- **WHEN** admin chọn môn và tải lên file có cột "Mã đăng nhập" (CCCD), "Tiến độ hoàn thành" và "Điểm kiểm tra"
- **THEN** hệ thống SHALL ghi kết quả môn đó cho từng học viên khớp theo CCCD trong khóa

#### Scenario: Học viên trong file không thuộc khóa
- **WHEN** một dòng có CCCD không khớp học viên nào trong khóa đã chọn
- **THEN** hệ thống SHALL báo dòng lỗi cụ thể và bỏ qua dòng đó

#### Scenario: Re-import cùng môn
- **WHEN** admin import lại file của môn đã có kết quả
- **THEN** hệ thống SHALL cập nhật kết quả môn theo CCCD thay vì tạo bản ghi trùng

### Requirement: Đánh giá đạt từng môn theo ngưỡng cố định

Hệ thống SHALL xác định một môn là "đạt" khi tiến độ hoàn thành lớn hơn 70 VÀ điểm kiểm tra lớn hơn 5.

#### Scenario: Môn đạt
- **WHEN** tiến độ hoàn thành > 70 và điểm kiểm tra > 5
- **THEN** hệ thống SHALL đánh dấu môn đó là đạt cho học viên

#### Scenario: Môn chưa đạt do tiến độ thấp
- **WHEN** tiến độ hoàn thành ≤ 70
- **THEN** hệ thống SHALL đánh dấu môn đó là chưa hoàn thành, bất kể điểm kiểm tra

#### Scenario: Môn chưa đạt do điểm thấp
- **WHEN** điểm kiểm tra ≤ 5
- **THEN** hệ thống SHALL đánh dấu môn đó là chưa kiểm tra đạt, bất kể tiến độ

### Requirement: Tổng hợp kết quả lý thuyết theo học viên

Hệ thống SHALL tổng hợp kết quả 6 môn của một học viên và xác định đạt lý thuyết khi cả 6 môn đều đạt.

#### Scenario: Tra cứu tổng hợp lý thuyết
- **WHEN** admin tra cứu theo học viên/CCCD/khóa
- **THEN** hệ thống SHALL hiển thị trạng thái từng môn và kết luận đạt/chưa đạt lý thuyết, nêu rõ môn nào chưa hoàn thành hoặc chưa kiểm tra
