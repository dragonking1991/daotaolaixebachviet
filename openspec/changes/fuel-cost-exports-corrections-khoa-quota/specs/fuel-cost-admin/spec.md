## ADDED Requirements

### Requirement: Xuất tổng hợp giáo viên đã duyệt (kế toán và admin)

Hệ thống SHALL cho phép **cả kế toán và admin** xuất Excel danh sách các giáo viên **đã duyệt** (đã quyết toán) trong một khoảng thời gian/kỳ.

#### Scenario: Xuất danh sách GV đã duyệt
- **WHEN** kế toán hoặc admin đang ở màn "Đã thanh toán" và bấm "Xuất tổng hợp đã duyệt" với bộ lọc kỳ và/hoặc khoảng ngày thanh toán
- **THEN** hệ thống SHALL tạo file Excel gồm, cho từng giáo viên đã quyết toán: STT, Giáo viên, Số học viên đã thanh toán, Số tiền, Ngày quyết toán; kèm dòng Tổng cộng và phần chữ ký

#### Scenario: Chỉ tính giáo viên đã duyệt
- **WHEN** một giáo viên có hóa đơn chưa quyết toán (đang chờ duyệt)
- **THEN** hệ thống SHALL không đưa giáo viên đó vào bản xuất "đã duyệt" (chỉ liệt kê giáo viên có hóa đơn/học viên đã quyết toán/đã thanh toán)

#### Scenario: Không có dữ liệu đã duyệt
- **WHEN** không có giáo viên nào đã duyệt trong phạm vi lọc
- **THEN** hệ thống SHALL không xuất file và báo cho người dùng rằng chưa có dữ liệu đã duyệt

### Requirement: Hủy duyệt thanh toán theo đợt

Hệ thống SHALL cho phép admin (quyền duyệt) tìm giáo viên đã duyệt theo tên và **hủy duyệt theo từng đợt (bảng kê)**, đưa dữ liệu về trạng thái chưa quyết toán mà không xóa hóa đơn/học viên.

#### Scenario: Lọc giáo viên đã duyệt theo tên
- **WHEN** admin nhập tên giáo viên tại màn "Đã thanh toán"
- **THEN** hệ thống SHALL liệt kê các đợt duyệt (bảng kê) của giáo viên đó kèm ngày lập, số học viên và tổng tiền

#### Scenario: Hủy duyệt một đợt
- **WHEN** admin chọn hủy duyệt một đợt (bảng kê)
- **THEN** hệ thống SHALL đặt lại các hóa đơn của đợt về chưa quyết toán (gỡ ngày thanh toán, đợt, đánh dấu duyệt), đặt lại các học viên của đợt về chưa thanh toán (gỡ ngày thanh toán, đợt, số tiền), xóa bản ghi đợt tương ứng, tất cả trong một giao dịch, và báo số bản ghi đã hoàn tác

#### Scenario: Chặn người không có quyền duyệt
- **WHEN** một tài khoản không có quyền duyệt cố gắng hủy duyệt một đợt
- **THEN** hệ thống SHALL từ chối hành động

### Requirement: Xuất Excel danh sách hóa đơn và học viên theo phạm vi thanh toán

Hệ thống SHALL cho phép xuất Excel tại trang danh sách Hóa đơn XD và Học viên XD theo bộ lọc hiện hành với ba phạm vi: Tổng danh sách, Đã thanh toán, Chưa thanh toán.

#### Scenario: Xuất hóa đơn theo phạm vi
- **WHEN** người dùng ở trang Hóa đơn XD chọn xuất với phạm vi "Tổng danh sách", "Đã thanh toán" (đã quyết toán) hoặc "Chưa thanh toán" (chưa quyết toán)
- **THEN** hệ thống SHALL xuất file Excel các hóa đơn khớp bộ lọc đang xem (từ khóa, khoảng ngày, kỳ) và đúng phạm vi được chọn, kèm dòng tổng số hóa đơn và tổng tiền

#### Scenario: Xuất học viên theo phạm vi
- **WHEN** người dùng ở trang Học viên XD chọn xuất với phạm vi "Tổng danh sách", "Đã thanh toán" (có ngày thanh toán) hoặc "Chưa thanh toán" (chưa có ngày thanh toán)
- **THEN** hệ thống SHALL xuất file Excel các học viên khớp bộ lọc đang xem (từ khóa, nhóm, khoảng ngày thanh toán) và đúng phạm vi được chọn, kèm dòng tổng số học viên

### Requirement: Định mức xăng dầu theo hạng khóa cho nhóm CK và DAT

Hệ thống SHALL cho phép cấu hình định mức XD (số chia để tính số học viên được trích) riêng cho từng tổ hợp nhóm (CK, DAT) và hạng khóa (bss, btd, c1, c, ce) lấy từ cột "Khóa" của file import; thuật toán lọc SHALL áp dụng định mức này.

#### Scenario: Cấu hình định mức theo hạng khóa
- **WHEN** admin nhập định mức XD cho một tổ hợp nhóm (CK hoặc DAT) và hạng khóa (ví dụ CK × c1)
- **THEN** hệ thống SHALL lưu giá trị đó và dùng nó khi tính số học viên được trích cho học viên thuộc nhóm và hạng khóa tương ứng

#### Scenario: Fallback khi không có cấu hình theo khóa
- **WHEN** một học viên nhóm CK/DAT có hạng khóa không nằm trong danh sách hoặc chưa được cấu hình định mức theo khóa
- **THEN** hệ thống SHALL dùng định mức XD chung của nhóm đó (rồi định mức mặc định) làm giá trị thay thế

#### Scenario: Nhận diện hạng khóa từ giá trị cột Khóa
- **WHEN** cột "Khóa" chứa giá trị có tiền tố/hậu tố (ví dụ `B11-BSS`, `C1.05`, `CE-2`)
- **THEN** hệ thống SHALL nhận diện đúng hạng khóa (bss, btd, c1, c, ce), ưu tiên khớp chuỗi dài trước để tránh nhầm giữa `c`, `c1`, `ce`

## MODIFIED Requirements

### Requirement: Admin sửa trạng thái hợp lệ và sửa/xóa/import bổ sung từng bản ghi

Hệ thống SHALL cho phép admin đổi trạng thái "Hợp lệ" của từng hóa đơn, xóa thủ công từng hóa đơn/học viên, và import bổ sung mà không xóa dữ liệu cũ — để xử lý sai sót mà không phải xóa toàn bộ.

#### Scenario: Đổi trạng thái hợp lệ của một hóa đơn
- **WHEN** admin đổi trạng thái "Hợp lệ" của một hóa đơn chưa quyết toán
- **THEN** hệ thống SHALL cập nhật cờ hợp lệ (hợp lệ ↔ không hợp lệ) và hóa đơn không hợp lệ SHALL bị loại khỏi thuật toán lọc/quyết toán

#### Scenario: Chặn đổi hợp lệ trên hóa đơn đã quyết toán
- **WHEN** admin cố đổi trạng thái hợp lệ của một hóa đơn đã quyết toán
- **THEN** hệ thống SHALL từ chối và giữ nguyên trạng thái

#### Scenario: Xóa thủ công một bản ghi
- **WHEN** admin xóa một hóa đơn hoặc học viên chưa quyết toán/chưa thanh toán
- **THEN** hệ thống SHALL xóa đúng bản ghi đó và giữ nguyên các bản ghi còn lại

#### Scenario: Import bổ sung không xóa dữ liệu cũ
- **WHEN** admin import thêm một file hóa đơn/học viên
- **THEN** hệ thống SHALL thêm bản ghi mới (và cập nhật bản ghi trùng theo khóa) mà không xóa các bản ghi đã có từ lần import trước
