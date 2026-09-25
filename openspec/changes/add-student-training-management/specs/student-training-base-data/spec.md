## ADDED Requirements

### Requirement: Import danh sách xe từ Excel

Hệ thống SHALL cho phép admin nạp danh sách xe tập lái từ file `.xlsx` theo mẫu (sheet `XE`), đọc cột theo tên tiêu đề.

#### Scenario: Import file xe hợp lệ
- **WHEN** admin tải lên file có các cột "Biển số xe", "Hạng xe tập lái", "Giáo viên" cùng các cột phụ (số khung, số máy, loại xe, nhãn hiệu...)
- **THEN** hệ thống SHALL tạo hoặc cập nhật bản ghi xe theo biển số, chuẩn hóa hạng xe (B11/B1/C1/C/CE) và lưu tên giáo viên cùng khóa chuẩn hóa tên (`gv_key`)

#### Scenario: Biển số đã tồn tại
- **WHEN** một dòng có biển số xe đã tồn tại
- **THEN** hệ thống SHALL cập nhật thông tin xe thay vì tạo bản ghi trùng

#### Scenario: File rỗng hoặc thiếu cột bắt buộc
- **WHEN** file không có dòng dữ liệu hợp lệ hoặc thiếu cột "Biển số xe"
- **THEN** hệ thống SHALL từ chối import và báo lỗi rõ ràng, không ghi dữ liệu

### Requirement: Import danh sách giáo viên từ Excel

Hệ thống SHALL cho phép admin nạp danh sách giáo viên từ file `.xlsx` theo mẫu (sheet `GiaoVien`).

#### Scenario: Import file giáo viên hợp lệ
- **WHEN** admin tải lên file có các cột "TenGV"/"HoTenDem", "SoCMT" (CCCD), "HangGPLX", "Hạng đào tạo được phép" và các cột liên hệ
- **THEN** hệ thống SHALL tạo hoặc cập nhật giáo viên theo CCCD, lưu khóa chuẩn hóa tên (`gv_key`) và các thông tin nghề nghiệp

#### Scenario: CCCD giáo viên đã tồn tại
- **WHEN** một dòng có CCCD giáo viên đã tồn tại
- **THEN** hệ thống SHALL cập nhật thông tin giáo viên thay vì tạo bản ghi trùng

### Requirement: Tra cứu thông tin xe và giáo viên

Hệ thống SHALL cho phép admin tra cứu xe theo biển số/hạng và giáo viên theo tên/CCCD.

#### Scenario: Tra cứu xe
- **WHEN** admin nhập biển số hoặc chọn hạng xe
- **THEN** hệ thống SHALL hiển thị danh sách xe khớp gồm biển số, hạng xe, giáo viên phụ trách

#### Scenario: Tra cứu giáo viên
- **WHEN** admin nhập tên hoặc CCCD giáo viên
- **THEN** hệ thống SHALL hiển thị giáo viên khớp gồm họ tên, CCCD, hạng GPLX và hạng được phép đào tạo
