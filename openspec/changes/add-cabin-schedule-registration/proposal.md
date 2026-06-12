## Why

Học viên hiện phải liên hệ văn phòng để được xếp lịch học cabin thủ công, gây tải cho bộ phận văn phòng và dễ trùng lịch. Trường cần một quy trình tự phục vụ: văn phòng tạo khóa học cabin và nạp danh sách học viên từ Excel (giống quy trình kỳ sát hạch đã có), còn học viên tự đăng nhập bằng CCCD để tự chọn và chốt ca học trống trong khung giờ cho phép.

## What Changes

- Thêm khái niệm "Khóa học cabin": admin tạo khóa với tên, thời gian bắt đầu/kết thúc đăng ký và sức chứa mỗi ca (mặc định 3 lịch/ca).
- Admin import danh sách học viên của khóa từ file `.xlsx`/`.xls` theo mẫu và xem đầy đủ các mục của học viên.
- Admin xem danh sách đăng ký theo từng ca/ngày và **xuất Excel** danh sách đã đăng ký để theo dõi.
- Trang công khai cho học viên: nhập CCCD để vào giao diện đăng ký, xem lưới lịch theo tuần (4 ca/ngày, mỗi ca 2 tiếng), chọn ca trống và bấm đăng ký để **chốt lịch**.
- Khung giờ cố định: Thứ 2–Thứ 6 có Ca 1 (8h–10h), Ca 2 (10h–12h), Ca 3 (12h–14h), Ca 4 (14h–16h); Thứ 7 chỉ sáng (Ca 1, Ca 2).
- Ca đã đủ số lượng đăng ký sẽ hiển thị trạng thái **block**, không cho học viên đăng ký tiếp.
- Cấu hình đăng ký theo thời gian: hết thời gian kết thúc của khóa, học viên không tự đăng ký được nữa.
- Hiển thị ghi chú hướng dẫn cho học viên (đến sớm 15 phút; liên hệ văn phòng nếu hết hạn đăng ký).

## Capabilities

### New Capabilities
- `cabin-course-management`: Quản trị khóa học cabin — tạo/sửa/xóa khóa, cấu hình thời gian đăng ký và sức chứa mỗi ca, import danh sách học viên từ Excel, xem chi tiết học viên và lịch đã đăng ký, xuất Excel danh sách đăng ký.
- `cabin-schedule-registration`: Đăng ký lịch học cabin công khai — học viên xác thực bằng CCCD, xem lưới ca theo khung giờ cố định, đăng ký (chốt) ca trống, và bị chặn khi ca đã đầy hoặc ngoài thời gian đăng ký.

### Modified Capabilities
<!-- Chưa có spec hiện hữu trong openspec/specs/; không có yêu cầu nào của capability cũ bị thay đổi. -->

## Impact

- **Database**: thêm bảng khóa học cabin và bảng đăng ký ca; danh sách học viên lưu theo mẫu của kỳ sát hạch (tái dùng `table_product` với `type='cabin'` và khóa liên kết).
- **Admin**: thêm `admin/sources/cabin.php` và bộ template `admin/templates/cabin/**`; mục menu admin mới; dùng lại PHPExcel để import/xuất.
- **Frontend**: thêm `sources/dangky_cabin.php`, `templates/cabin/**`, và AJAX handler công khai cho tra cứu CCCD + đăng ký ca.
- **Routing**: thêm tuyến công khai trong `libraries/router.php`.
- **Type config**: bổ sung `cabin` vào cấu hình loại bản ghi import (`libraries/type/`).
- **Dependencies**: không thêm thư viện mới; tái dùng `libraries/PHPExcel`, `libraries/class/class.PDODb.php`, `libraries/class/class.Functions.php`.
