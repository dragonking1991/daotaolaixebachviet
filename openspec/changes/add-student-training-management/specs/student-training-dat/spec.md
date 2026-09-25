## ADDED Requirements

### Requirement: Import phiên học DAT chống trùng

Hệ thống SHALL cho phép admin nạp file DAT của Cục (`.xlsx`) hằng ngày, lưu từng phiên học và chống trùng theo mã phiên học.

#### Scenario: Import phiên học mới
- **WHEN** admin tải lên file DAT có các cột mã phiên học, thời gian bắt đầu/kết thúc phiên, thời gian thực hành, quãng đường, mã học viên, hạng đào tạo, biển số xe, giáo viên, trạng thái
- **THEN** hệ thống SHALL chèn các phiên có mã phiên học chưa tồn tại, gắn với học viên khớp theo mã học viên

#### Scenario: Phiên học đã tồn tại
- **WHEN** file chứa mã phiên học đã có trong hệ thống
- **THEN** hệ thống SHALL bỏ qua các phiên trùng, đồng thời báo và xác nhận số phiên chưa có đã được import

#### Scenario: Học viên trong file không thuộc khóa
- **WHEN** một phiên có mã học viên không khớp học viên nào
- **THEN** hệ thống SHALL báo dòng lỗi cụ thể và bỏ qua phiên đó

### Requirement: Tính tham số DAT theo phiên và cộng dồn theo học viên

Hệ thống SHALL tính các tham số A, B, C, D, E từ các phiên khả dụng của mỗi học viên.

#### Scenario: Tính giờ đêm B theo từng phiên
- **WHEN** một phiên có thời gian rơi vào khung 18h–5h sáng
- **THEN** hệ thống SHALL tính phần thời gian đêm của phiên đó theo phút và cộng vào tổng giờ đêm B của học viên

#### Scenario: Phân loại giờ tự động và số sàn
- **WHEN** phiên dùng xe số tự động
- **THEN** hệ thống SHALL cộng thời gian phiên vào giờ tự động C; ngược lại cộng vào giờ số sàn D

#### Scenario: Cộng dồn tổng
- **WHEN** hệ thống tổng hợp DAT cho một học viên
- **THEN** hệ thống SHALL tính A = C + D, tổng B (đêm), tổng E (km) từ tất cả phiên khả dụng

### Requirement: Đánh giá đạt DAT theo hạng

Hệ thống SHALL xác định đạt DAT khi thỏa cả điều kiện giờ và quãng đường của hạng đã chuẩn hóa, với điều kiện bắt buộc mọi hạng B là B(đêm) ≥ 1 giờ.

#### Scenario: Đạt hạng B11 (số tự động)
- **WHEN** học viên hạng B11 có A ≥ 12, B ≥ 1 và E ≥ 710 km
- **THEN** hệ thống SHALL đánh dấu đạt DAT

#### Scenario: Đạt hạng B1 (số cơ khí)
- **WHEN** học viên hạng B1 có A ≥ 20, B ≥ 1, C ≥ 1, D ≥ 19 và E ≥ 810 km
- **THEN** hệ thống SHALL đánh dấu đạt DAT

#### Scenario: Đạt hạng C1
- **WHEN** học viên hạng C1 có A ≥ 24, B ≥ 1, C ≥ 1, D ≥ 23 và E ≥ 825 km
- **THEN** hệ thống SHALL đánh dấu đạt DAT

#### Scenario: Đạt hạng C và CE
- **WHEN** học viên hạng C hoặc CE có A ≥ 5, B ≥ 1 và E ≥ 210 km
- **THEN** hệ thống SHALL đánh dấu đạt DAT

#### Scenario: Chưa đủ điều kiện
- **WHEN** thiếu bất kỳ điều kiện giờ hoặc quãng đường của hạng
- **THEN** hệ thống SHALL đánh dấu chưa đạt DAT

### Requirement: Tra cứu chi tiết DAT

Hệ thống SHALL cho phép tra cứu DAT theo ngày học, học viên, CCCD, khóa.

#### Scenario: Tra cứu phiên và tổng hợp DAT
- **WHEN** admin tra cứu theo ngày/học viên/CCCD/khóa
- **THEN** hệ thống SHALL hiển thị mã phiên, mã và tên học viên, ngày sinh, khóa, hạng, các tham số A/B/C/D và quãng đường, biển số xe, tên giáo viên
