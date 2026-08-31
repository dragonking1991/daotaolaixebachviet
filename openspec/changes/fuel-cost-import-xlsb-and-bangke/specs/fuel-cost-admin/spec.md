## MODIFIED Requirements

### Requirement: Admin import danh sách hóa đơn xăng dầu hợp lệ

Hệ thống SHALL cho phép admin nạp danh sách hóa đơn XD của giáo viên theo kỳ từ file Excel (`.xlsx`, `.xls`, `.xlsb`), gắn mỗi hóa đơn với giáo viên qua **tên** (cột `GV`) và lưu đủ chi tiết phục vụ bảng kê.

#### Scenario: Import file hợp lệ với đủ chi tiết
- **WHEN** admin tải lên file gồm Số hóa đơn, Ngày, Thông tin bán hàng, Chi tiết, Số tiền HĐ, Biển số xe, GV
- **THEN** hệ thống SHALL lưu từng hóa đơn với Số HĐ, Ngày, Thông tin bán hàng, Chi tiết, Số tiền, Biển số xe và tên giáo viên (chuẩn hóa thành khóa định danh), trạng thái chưa quyết toán

#### Scenario: Nhận diện cột linh hoạt theo tiêu đề thật
- **WHEN** file có tiêu đề cột khác mẫu (ví dụ tiền dạng số theo đơn vị nghìn, ngày ở dạng serial Excel)
- **THEN** hệ thống SHALL nhận diện đúng cột theo tiêu đề và diễn giải đúng giá trị tiền (đơn vị nghìn cho ô số, đủ VND cho ô chuỗi có dấu phẩy) và ngày (theo serial Excel)

#### Scenario: Chống trùng theo mã và ngày hóa đơn
- **WHEN** một dòng có cặp Mã HĐ và Ngày HĐ đã tồn tại trong hệ thống
- **THEN** hệ thống SHALL không tạo bản ghi trùng và báo cho admin dòng bị trùng

#### Scenario: File không đọc được hoặc rỗng
- **WHEN** hệ thống đọc file và không có dòng hóa đơn hợp lệ nào
- **THEN** hệ thống SHALL từ chối import và báo lỗi rõ ràng cho admin

### Requirement: Admin import danh sách học viên theo nhóm

Hệ thống SHALL cho phép admin nạp danh sách học viên từ file Excel (`.xlsx`, `.xls`, `.xlsb`), nhận diện đúng các cột thật của trường và hiển thị đầy đủ thông tin.

#### Scenario: Nhận diện cột giáo viên và nhóm theo biến thể tiêu đề thật
- **WHEN** file dùng cột `PHÂN XE` cho giáo viên phụ trách và cột `GHI CHÚ` chứa giá trị nhóm (BT/CK/DAT), kèm cột `NGƯỜI NỘP`, `KHÓA`, `Số CCCD/CC`
- **THEN** hệ thống SHALL nhận diện giáo viên từ cột `PHÂN XE` (hoặc `GIÁO VIÊN`), nhóm từ cột `GHI CHÚ` (hoặc `Nhóm`), và lưu Họ tên, Khóa, Ngày sinh, CCCD, Người nộp, Giáo viên, Nhóm

#### Scenario: Nhận diện cột nhóm theo giá trị khi tiêu đề mơ hồ
- **WHEN** tiêu đề cột nhóm không rõ ràng nhưng một cột chứa chủ yếu các giá trị BT, CK, DAT
- **THEN** hệ thống SHALL chọn cột đó làm cột nhóm

#### Scenario: Chặn cả file khi có CCCD trùng
- **WHEN** file import chứa ít nhất một CCCD đã tồn tại trên hệ thống hoặc lặp trong chính file
- **THEN** hệ thống SHALL không lưu bất kỳ dòng nào và trả về danh sách lỗi chi tiết theo từng dòng

#### Scenario: Hiển thị đầy đủ thông tin sau import
- **WHEN** admin mở danh sách học viên đã import
- **THEN** hệ thống SHALL hiển thị Họ tên, Khóa, Ngày sinh, CCCD, Nhóm, Giáo viên phụ trách và trạng thái/ngày thanh toán

### Requirement: Hỗ trợ import định dạng .xlsb

Hệ thống SHALL cho phép admin tải lên file `.xlsb` cho cả hóa đơn và học viên, và xử lý bằng cách chuyển đổi sang định dạng đọc được.

#### Scenario: Máy chủ có công cụ chuyển đổi
- **WHEN** admin tải lên file `.xlsb` và máy chủ có công cụ chuyển đổi (LibreOffice hoặc tương đương)
- **THEN** hệ thống SHALL chuyển đổi sang `.xlsx`, chọn đúng sheet dữ liệu theo tên (ví dụ `học viên`) và import như file `.xlsx`

#### Scenario: Máy chủ không có công cụ chuyển đổi
- **WHEN** admin tải lên file `.xlsb` nhưng máy chủ không có công cụ chuyển đổi khả dụng
- **THEN** hệ thống SHALL từ chối và hướng dẫn admin lưu file dưới dạng `.xlsx` rồi import lại

### Requirement: Xuất bảng kê trích chi phí và cập nhật trạng thái thanh toán

Hệ thống SHALL cho phép admin xuất "Bảng kê trích chi phí nhiên liệu" theo từng giáo viên đúng mẫu và tự động ghi nhận ngày thanh toán cho các học viên được trích.

#### Scenario: Xuất bảng kê đúng mẫu theo giáo viên
- **WHEN** admin xuất bảng kê sau khi chạy thuật toán lọc cho một đợt
- **THEN** hệ thống SHALL tạo file Excel gồm, cho từng giáo viên: tiêu đề trung tâm, tên giáo viên, ngày quyết toán; bảng **Nội dung** (STT, Số hóa đơn, Ngày, Thông tin bán hàng, Chi tiết, Số tiền HĐ, Biển số xe, Tổng cộng); bảng **Danh sách học viên** (STT, Khóa, Họ tên, CCCD/CC, Năm sinh, Định mức, Số tiền thanh toán, Nhóm, Tổng cộng); và các dòng chữ ký (Phòng Đào tạo, Kế Toán, Giáo viên quyết toán)

#### Scenario: Nhiều giáo viên trong một đợt
- **WHEN** đợt lọc có nhiều giáo viên
- **THEN** hệ thống SHALL xuất mỗi giáo viên trên một sheet riêng trong cùng một workbook

#### Scenario: Tự động ghi ngày thanh toán và khóa hóa đơn
- **WHEN** admin xuất bảng kê / xác nhận thanh toán
- **THEN** hệ thống SHALL ghi ngày thanh toán bằng ngày thực hiện cho các học viên được trích và đánh dấu các hóa đơn liên quan là đã quyết toán, đồng thời loại các học viên đã thanh toán khỏi các đợt lọc sau
