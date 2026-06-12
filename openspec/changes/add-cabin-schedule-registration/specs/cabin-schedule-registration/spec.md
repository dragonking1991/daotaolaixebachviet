## ADDED Requirements

### Requirement: Học viên xác thực bằng CCCD để vào giao diện đăng ký

Hệ thống SHALL cho phép học viên nhập CCCD để mở giao diện đăng ký lịch học cabin của khóa tương ứng.

#### Scenario: CCCD khớp học viên trong khóa
- **WHEN** học viên nhập CCCD khớp với một học viên thuộc khóa đang mở đăng ký
- **THEN** hệ thống SHALL mở giao diện đăng ký gắn với học viên đó

#### Scenario: CCCD không khớp
- **WHEN** học viên nhập CCCD không khớp học viên nào trong khóa
- **THEN** hệ thống SHALL hiển thị thông báo không tìm thấy và gợi ý liên hệ văn phòng

#### Scenario: Chuẩn hóa CCCD 11/12 số
- **WHEN** học viên nhập CCCD ở biến thể thiếu hoặc thừa số 0 ở đầu
- **THEN** hệ thống SHALL chuẩn hóa và vẫn khớp đúng bản ghi học viên nếu tồn tại

### Requirement: Hiển thị lưới ca theo khung giờ cố định

Hệ thống SHALL hiển thị lưới đăng ký theo tuần với các ca cố định trong khoảng thời gian của khóa.

#### Scenario: Khung giờ ngày Thứ 2 đến Thứ 6
- **WHEN** học viên xem một ngày từ Thứ 2 đến Thứ 6
- **THEN** hệ thống SHALL hiển thị 4 ca: Ca 1 (8h–10h), Ca 2 (10h–12h), Ca 3 (12h–14h), Ca 4 (14h–16h)

#### Scenario: Khung giờ ngày Thứ 7
- **WHEN** học viên xem ngày Thứ 7
- **THEN** hệ thống SHALL chỉ hiển thị Ca 1 (8h–10h) và Ca 2 (10h–12h)

#### Scenario: Ngày ngoài phạm vi khóa
- **WHEN** một ngày nằm ngoài khoảng [ngày bắt đầu, ngày kết thúc] của khóa hoặc là Chủ nhật
- **THEN** hệ thống SHALL không cho đăng ký ca trong ngày đó

### Requirement: Học viên đăng ký và chốt ca trống

Hệ thống SHALL cho phép học viên chọn các ca còn trống và chốt lịch đăng ký, tối đa 3 ca trong cùng một khóa.

#### Scenario: Đăng ký ca còn chỗ
- **WHEN** học viên bấm đăng ký một ca có số đăng ký nhỏ hơn sức chứa mỗi ca
- **THEN** hệ thống SHALL ghi nhận đăng ký và chốt ca đó cho học viên

#### Scenario: Học viên đăng ký nhiều ca trong cùng khóa
- **WHEN** học viên đã có ít nhất 1 ca và tổng số ca đã đăng ký trong khóa vẫn nhỏ hơn 3
- **THEN** hệ thống SHALL cho phép học viên đăng ký thêm ca mới mà không ghi đè các ca đã có

#### Scenario: Học viên chạm giới hạn 3 ca
- **WHEN** học viên đã đăng ký đủ 3 ca trong cùng khóa và tiếp tục chọn một ca khác
- **THEN** hệ thống SHALL từ chối yêu cầu ở phía server và hiển thị trạng thái đã đạt tối đa 3 ca trên giao diện

#### Scenario: Học viên hủy một ca đã đăng ký để đổi ca khác
- **WHEN** học viên chọn hủy một ca chưa diễn ra trong thời gian khóa vẫn còn mở đăng ký
- **THEN** hệ thống SHALL xóa đăng ký của ca đó, cập nhật lại số chỗ còn trống và cho phép học viên chọn ca khác

#### Scenario: Không cho tự hủy ca đã qua hoặc sau khi khóa kết thúc
- **WHEN** học viên cố hủy một ca đã qua hoặc khi khóa đã kết thúc
- **THEN** hệ thống SHALL từ chối yêu cầu hủy ở phía server

#### Scenario: Học viên đăng ký trùng cùng một ca
- **WHEN** học viên bấm đăng ký một ca mà chính họ đã đăng ký trước đó
- **THEN** hệ thống SHALL từ chối và không tạo bản ghi đăng ký trùng

#### Scenario: Tranh chỗ cuối cùng đồng thời
- **WHEN** nhiều học viên cùng đăng ký ô ca cuối cùng gần như đồng thời
- **THEN** hệ thống SHALL chỉ chấp nhận số đăng ký đúng bằng sức chứa và từ chối phần vượt

### Requirement: Chặn đăng ký khi ca đã đầy

Hệ thống SHALL hiển thị trạng thái block và không cho đăng ký các ca đã đạt sức chứa.

#### Scenario: Ca đã đủ số lượng
- **WHEN** một ca có số đăng ký bằng sức chứa mỗi ca
- **THEN** hệ thống SHALL hiển thị ca đó ở trạng thái block và không cho học viên đăng ký

#### Scenario: Sức chứa cabin tính dùng chung toàn hệ thống
- **WHEN** nhiều khóa học cùng có học viên đăng ký vào cùng một ngày và cùng một ca
- **THEN** hệ thống SHALL cộng dồn toàn bộ đăng ký đó để so với sức chứa của cabin

### Requirement: Kiểm soát thời gian đăng ký theo khóa

Hệ thống SHALL chỉ cho học viên tự đăng ký trong thời gian đăng ký của khóa.

#### Scenario: Trong thời gian đăng ký
- **WHEN** thời điểm hiện tại nằm trong hoặc trước ngày kết thúc của khóa
- **THEN** hệ thống SHALL cho phép học viên đăng ký ca trống

#### Scenario: Sau thời gian kết thúc đăng ký
- **WHEN** thời điểm hiện tại vượt quá ngày kết thúc của khóa
- **THEN** hệ thống SHALL từ chối mọi yêu cầu đăng ký ở phía server và chuyển giao diện sang chỉ-đọc kèm hướng dẫn liên hệ văn phòng

### Requirement: Hiển thị ghi chú hướng dẫn cho học viên

Hệ thống SHALL hiển thị ghi chú hướng dẫn trên giao diện đăng ký.

#### Scenario: Hiển thị ghi chú
- **WHEN** học viên xem giao diện đăng ký
- **THEN** hệ thống SHALL hiển thị ghi chú yêu cầu có mặt trước giờ học 15 phút và hướng dẫn liên hệ văn phòng nếu không đăng ký được sau thời gian kết thúc
