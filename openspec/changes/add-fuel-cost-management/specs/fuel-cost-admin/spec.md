## ADDED Requirements

### Requirement: Admin cấu hình tham số thanh toán xăng dầu

Hệ thống SHALL cho phép admin cấu hình các tham số tính toán thanh toán xăng dầu: định mức XD cho mỗi học viên, mức thanh toán nhóm BT, và mức thanh toán nhóm CK và DAT.

#### Scenario: Giá trị mặc định khi chưa cấu hình
- **WHEN** admin mở màn cấu hình lần đầu mà chưa lưu tham số nào
- **THEN** hệ thống SHALL hiển thị định mức XD mặc định `3.500.000`, mức nhóm BT mặc định `1.200.000`, mức nhóm CK và DAT mặc định `0`

#### Scenario: Cập nhật tham số
- **WHEN** admin nhập giá trị mới cho định mức hoặc mức thanh toán rồi lưu
- **THEN** hệ thống SHALL lưu giá trị mới và dùng cho các lần chạy thuật toán lọc thanh toán tiếp theo

#### Scenario: Giá trị không hợp lệ
- **WHEN** admin nhập một tham số là số âm hoặc không phải số
- **THEN** hệ thống SHALL từ chối giá trị đó và giữ nguyên hoặc đặt về 0 theo quy tắc chuẩn hóa, không lưu giá trị âm

### Requirement: Admin import danh sách hóa đơn xăng dầu hợp lệ

Hệ thống SHALL cho phép admin nạp danh sách hóa đơn XD của giáo viên theo kỳ từ file Excel, gắn mỗi hóa đơn với giáo viên qua CCCD.

#### Scenario: Import file hợp lệ
- **WHEN** admin tải lên file Excel gồm Mã HĐ, Ngày HĐ, Tổng tiền, CCCD giáo viên, Tên giáo viên, Kỳ từ dòng 2 trở đi
- **THEN** hệ thống SHALL lưu từng hóa đơn vào cơ sở dữ liệu với trạng thái chưa quyết toán

#### Scenario: Chống trùng theo mã và ngày hóa đơn
- **WHEN** một dòng có cặp Mã HĐ và Ngày HĐ đã tồn tại trong hệ thống
- **THEN** hệ thống SHALL không tạo bản ghi trùng và báo cho admin dòng bị trùng

#### Scenario: Chặn import đè kỳ đã tổng hợp
- **WHEN** admin cố import một kỳ mà kỳ đó đã có dữ liệu hóa đơn trong hệ thống
- **THEN** hệ thống SHALL từ chối import đè và thông báo rõ kỳ đã được import trước đó

#### Scenario: Khóa hóa đơn đã quyết toán
- **WHEN** một dòng import trùng với hóa đơn đã được đưa vào quyết toán thanh toán
- **THEN** hệ thống SHALL giữ nguyên hóa đơn đã quyết toán và không cho phép ghi đè

#### Scenario: File không đọc được hoặc rỗng
- **WHEN** hệ thống đọc file Excel và không có dòng hóa đơn hợp lệ nào
- **THEN** hệ thống SHALL từ chối import và báo lỗi rõ ràng cho admin

### Requirement: Admin import danh sách học viên theo nhóm

Hệ thống SHALL cho phép admin nạp danh sách học viên từ file Excel, mỗi học viên thuộc một trong ba nhóm BT, CK, DAT và gắn với giáo viên phụ trách qua CCCD.

#### Scenario: Import file hợp lệ
- **WHEN** admin tải lên file Excel gồm Họ tên, Ngày sinh, CCCD, Nhóm, CCCD giáo viên, Tên giáo viên từ dòng 2 trở đi và không có CCCD nào trùng
- **THEN** hệ thống SHALL lưu toàn bộ học viên với nhóm và giáo viên phụ trách tương ứng

#### Scenario: Chặn cả file khi có CCCD trùng
- **WHEN** file import chứa ít nhất một CCCD đã tồn tại trên hệ thống hoặc lặp trong chính file
- **THEN** hệ thống SHALL không lưu bất kỳ dòng nào vào cơ sở dữ liệu và trả về danh sách lỗi chi tiết theo từng dòng, ví dụ "Dòng 15: CCCD 0123456789xx đã tồn tại trên hệ thống"

#### Scenario: Nhóm không hợp lệ
- **WHEN** một dòng có giá trị nhóm không thuộc BT, CK hoặc DAT
- **THEN** hệ thống SHALL báo lỗi dòng đó và không lưu file

### Requirement: Thuật toán lọc thanh toán tự động theo giáo viên

Hệ thống SHALL tự động xác định số học viên tối đa được duyệt thanh toán cho mỗi giáo viên dựa trên tổng hóa đơn hợp lệ và định mức đã cấu hình.

#### Scenario: Xác định số học viên tối đa
- **WHEN** admin chạy thuật toán lọc cho một kỳ và một giáo viên có tổng hóa đơn hợp lệ chưa quyết toán là $S_{HĐ}$
- **THEN** hệ thống SHALL chọn số học viên tối đa $N$ thỏa mãn $N \times \text{định mức} \le S_{HĐ}$

#### Scenario: Ví dụ nghiệp vụ
- **WHEN** giáo viên có tổng hóa đơn `13.500.000` và định mức là `3.500.000`
- **THEN** hệ thống SHALL chọn đúng 3 học viên (vì $3 \times 3.500.000 = 10.500.000 \le 13.500.000$ còn $4 \times 3.500.000 = 14.000.000 > 13.500.000$)

#### Scenario: Chọn học viên chưa thanh toán đầu tiên
- **WHEN** hệ thống chọn $N$ học viên cho một giáo viên
- **THEN** hệ thống SHALL chỉ lấy các học viên chưa có ngày thanh toán, theo thứ tự ổn định, và bỏ qua học viên đã thanh toán ở đợt trước

#### Scenario: Gán số tiền theo nhóm
- **WHEN** một học viên được chọn vào danh sách thanh toán
- **THEN** hệ thống SHALL gán số tiền `1.200.000` (hoặc mức BT hiện hành) cho học viên nhóm BT và `0` (hoặc mức tương ứng) cho học viên nhóm CK và DAT

### Requirement: Xuất bảng kê trích chi phí và cập nhật trạng thái thanh toán

Hệ thống SHALL cho phép admin xuất bảng kê trích chi phí nhiên liệu và tự động ghi nhận ngày thanh toán cho các học viên được trích.

#### Scenario: Xuất bảng kê Excel
- **WHEN** admin bấm xuất bảng kê trích chi phí nhiên liệu sau khi chạy thuật toán lọc
- **THEN** hệ thống SHALL tạo file Excel gồm Họ tên, CCCD, Nhóm, Giáo viên, Định mức, Số tiền thanh toán và Ngày thanh toán của các học viên được trích

#### Scenario: Tự động ghi ngày thanh toán
- **WHEN** admin xuất bảng kê hoặc xác nhận thanh toán
- **THEN** hệ thống SHALL ghi ngày thanh toán bằng ngày thực hiện cho tất cả học viên trong danh sách được trích và đánh dấu các hóa đơn liên quan là đã quyết toán

#### Scenario: Loại học viên đã thanh toán khỏi đợt sau
- **WHEN** admin chạy lại thuật toán lọc ở đợt tiếp theo
- **THEN** hệ thống SHALL không kéo các học viên đã có ngày thanh toán vào danh sách lọc mới
