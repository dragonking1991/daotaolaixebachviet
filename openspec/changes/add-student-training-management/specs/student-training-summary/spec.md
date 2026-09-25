## ADDED Requirements

### Requirement: Tổng hợp Đạt/Không đạt 4 module

Hệ thống SHALL tổng hợp kết quả 4 module (Lý thuyết, Cabin, Thực hành trong hình, DAT) của mỗi học viên và kết luận đủ/chưa đủ điều kiện kiểm tra.

#### Scenario: Học viên đủ điều kiện
- **WHEN** một học viên đạt cả 4 module
- **THEN** hệ thống SHALL kết luận học viên đủ điều kiện kiểm tra

#### Scenario: Học viên chưa đủ điều kiện
- **WHEN** một học viên chưa đạt ít nhất một module
- **THEN** hệ thống SHALL kết luận chưa đủ điều kiện và nêu rõ module còn thiếu

### Requirement: Tra cứu tổng hợp đa tiêu chí

Hệ thống SHALL cho phép admin tra cứu tổng hợp theo khoảng ngày (từ ngày – tới ngày), CCCD, họ tên học viên, khóa, hạng, giáo viên.

#### Scenario: Lọc theo nhiều tiêu chí
- **WHEN** admin chọn khoảng ngày và/hoặc CCCD/họ tên/khóa/hạng/giáo viên
- **THEN** hệ thống SHALL hiển thị danh sách học viên khớp cùng trạng thái từng module và kết luận tổng

### Requirement: Xuất Excel danh sách Đạt/Không đạt

Hệ thống SHALL cho phép admin xuất Excel danh sách học viên Đạt và Không đạt kèm chi tiết 4 module.

#### Scenario: Xuất Excel tổng hợp
- **WHEN** admin chọn xuất Excel theo bộ lọc hiện tại
- **THEN** hệ thống SHALL tạo file `.xlsx` gồm mã học viên, tên, hạng, khóa, trạng thái từng module và chi tiết tham số (tổng giờ H, tổng quãng đường H, tổng giờ Đ, tổng quãng đường Đ, A, B, C, D)
