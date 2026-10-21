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

### Requirement: Thao tác hàng loạt và thống kê lý thuyết

Hệ thống SHALL cho phép admin chọn nhiều học viên để đánh dấu đạt hoặc xóa kết quả lý thuyết; thao tác đánh dấu đạt SHALL áp dụng cho toàn bộ môn bắt buộc theo hạng của từng học viên. Trang lý thuyết SHALL thống kê số đạt/chưa đạt cho từng môn và tổng kết lý thuyết theo danh sách lọc.

#### Scenario: Đánh dấu đạt nhiều học viên
- **WHEN** admin chọn học viên và xác nhận đánh dấu đạt
- **THEN** hệ thống SHALL cập nhật trạng thái tất cả môn bắt buộc theo hạng, đồng thời đồng bộ trạng thái mô phỏng giữa dữ liệu lý thuyết và module mô phỏng riêng

#### Scenario: Xóa kết quả lý thuyết đã chọn
- **WHEN** admin chọn học viên và xác nhận xóa kết quả lý thuyết
- **THEN** hệ thống SHALL xóa kết quả lý thuyết và mô phỏng của những học viên đã chọn, không tác động học viên khác

#### Scenario: Theo dõi số đạt/chưa đạt
- **WHEN** admin xem trang lý thuyết với bộ lọc hiện tại
- **THEN** hệ thống SHALL hiển thị số học viên đạt/chưa đạt tổng kết và từng môn, không để bộ lọc trạng thái làm mất số liệu đối chiếu
