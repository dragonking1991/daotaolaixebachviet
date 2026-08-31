## MODIFIED Requirements

### Requirement: Giáo viên đăng nhập cổng tra cứu bằng CCCD

Hệ thống SHALL cho phép giáo viên tra cứu chi phí xăng dầu bằng cách nhập số CCCD của mình (giống tra cứu nhân viên), bắc cầu qua hồ sơ nhân viên để khớp dữ liệu XD lưu theo tên giáo viên.

#### Scenario: CCCD khớp hồ sơ nhân viên và có dữ liệu XD
- **WHEN** giáo viên nhập số CCCD tồn tại trong hồ sơ nhân viên và có dữ liệu xăng dầu theo tên
- **THEN** hệ thống SHALL xác định tên giáo viên từ hồ sơ nhân viên và hiển thị dữ liệu XD của đúng giáo viên đó

#### Scenario: CCCD không có trong hồ sơ nhân viên
- **WHEN** giáo viên nhập số CCCD không có trong hồ sơ nhân viên
- **THEN** hệ thống SHALL thông báo không tìm thấy và hướng dẫn liên hệ văn phòng, không hiển thị dữ liệu của giáo viên khác

#### Scenario: Chuẩn hóa biến thể CCCD
- **WHEN** giáo viên nhập CCCD ở dạng biến thể (11 hoặc 12 chữ số, có khoảng trắng thừa)
- **THEN** hệ thống SHALL chuẩn hóa và đối chiếu tương đương khi tra hồ sơ nhân viên

### Requirement: Giáo viên xem danh sách hóa đơn xăng dầu

Hệ thống SHALL hiển thị cho giáo viên danh sách hóa đơn XD của mình trong khoảng thời gian đã chọn, khớp theo tên giáo viên.

#### Scenario: Hiển thị hóa đơn theo khoảng thời gian
- **WHEN** giáo viên tra cứu với CCCD hợp lệ và chọn Từ ngày – Đến ngày
- **THEN** hệ thống SHALL hiển thị bảng gồm Ngày hóa đơn, Số hóa đơn và Tiền hóa đơn của các hóa đơn của giáo viên đó trong khoảng thời gian

### Requirement: Giáo viên xem danh sách học viên đã thanh toán

Hệ thống SHALL hiển thị cho giáo viên danh sách học viên đã được thanh toán trong khoảng thời gian đã chọn, khớp theo tên giáo viên.

#### Scenario: Hiển thị học viên đã thanh toán
- **WHEN** giáo viên tra cứu với CCCD hợp lệ và chọn khoảng thời gian
- **THEN** hệ thống SHALL hiển thị bảng gồm Họ tên, CCCD, Nhóm, Định mức XD, Số tiền được thanh toán và Ngày thanh toán của từng học viên đã thanh toán
