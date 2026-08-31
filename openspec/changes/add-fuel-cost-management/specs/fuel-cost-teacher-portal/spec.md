## ADDED Requirements

### Requirement: Giáo viên đăng nhập cổng tra cứu bằng CCCD

Hệ thống SHALL cho phép giáo viên truy cập cổng tra cứu chi phí xăng dầu bằng cách nhập số CCCD của mình.

#### Scenario: CCCD khớp giáo viên có dữ liệu
- **WHEN** giáo viên nhập số CCCD tồn tại trong dữ liệu hóa đơn hoặc học viên
- **THEN** hệ thống SHALL mở giao diện tra cứu cho đúng giáo viên đó

#### Scenario: CCCD không khớp
- **WHEN** giáo viên nhập số CCCD không tồn tại trong dữ liệu XD
- **THEN** hệ thống SHALL thông báo không tìm thấy dữ liệu và không hiển thị thông tin của giáo viên khác

#### Scenario: Chuẩn hóa biến thể CCCD
- **WHEN** giáo viên nhập CCCD có định dạng biến thể (ví dụ 11 hoặc 12 chữ số, có khoảng trắng thừa)
- **THEN** hệ thống SHALL chuẩn hóa và đối chiếu tương đương với CCCD đã lưu

### Requirement: Giáo viên lọc dữ liệu theo khoảng thời gian

Hệ thống SHALL cho phép giáo viên lọc kết quả tra cứu theo khoảng thời gian Từ ngày đến Đến ngày.

#### Scenario: Lọc theo khoảng ngày
- **WHEN** giáo viên chọn Từ ngày và Đến ngày rồi tra cứu
- **THEN** hệ thống SHALL chỉ hiển thị dữ liệu nằm trong khoảng thời gian đã chọn

### Requirement: Giáo viên xem danh sách hóa đơn xăng dầu

Hệ thống SHALL hiển thị cho giáo viên danh sách hóa đơn XD của mình trong khoảng thời gian đã chọn.

#### Scenario: Hiển thị hóa đơn
- **WHEN** giáo viên tra cứu với CCCD hợp lệ và khoảng thời gian
- **THEN** hệ thống SHALL hiển thị bảng gồm Ngày hóa đơn, Số hóa đơn và Tiền hóa đơn của các hóa đơn trong khoảng thời gian đó

### Requirement: Giáo viên xem danh sách học viên đã thanh toán

Hệ thống SHALL hiển thị cho giáo viên danh sách học viên đã được thanh toán trong khoảng thời gian đã chọn.

#### Scenario: Hiển thị học viên đã thanh toán
- **WHEN** giáo viên tra cứu với CCCD hợp lệ và khoảng thời gian
- **THEN** hệ thống SHALL hiển thị bảng gồm Họ tên, CCCD, Nhóm (BT/CK/DAT), Định mức XD, Số tiền được thanh toán và Ngày thanh toán của từng học viên

#### Scenario: Chỉ hiển thị học viên đã có ngày thanh toán
- **WHEN** một học viên của giáo viên chưa được thanh toán
- **THEN** hệ thống SHALL không đưa học viên đó vào bảng danh sách đã thanh toán
