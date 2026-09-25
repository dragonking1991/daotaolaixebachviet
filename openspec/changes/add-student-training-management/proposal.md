## Why

Trung tâm cần một phần mềm quản lý **toàn bộ quá trình đào tạo của học viên từ khai giảng đến đủ điều kiện kiểm tra**, thay cho việc tổng hợp thủ công nhiều file Excel rời rạc (danh sách xe, giáo viên, học viên, 6 môn lý thuyết, cabin, DAT của Cục). Hiện mỗi bộ phận giữ một file riêng; muốn biết một học viên đã đủ điều kiện kiểm tra hay chưa phải mở và đối chiếu nhiều file bằng tay — dễ sai sót và tốn thời gian. Hệ thống mới sẽ nạp các file này vào một nơi, tự động tính Đạt/Không đạt theo quy định của từng hạng, và cho tra cứu theo học viên/CCCD/khóa.

## What Changes

- **Dữ liệu nền**: Import và tra cứu danh sách **xe** (biển số, hạng xe tập lái, giáo viên phụ trách) và **giáo viên** (CCCD, hạng được phép dạy) từ file `.xlsx` theo mẫu.
- **Khóa học & học viên**: Admin tạo khóa với 5 thông tin (hạng, khóa, mã khóa học, ngày khai giảng, ngày mãn khóa) rồi import danh sách học viên (mẫu 2) có **cả mã học viên trung tâm và CCCD** để lập bảng ánh xạ dùng chung cho mọi module.
- **Module Lý thuyết**: Import 6 môn (Cấu tạo, Kỹ thuật lái, Phần 1, Phần 2, Phần 3, Đạo đức) theo CCCD; đạt khi **tiến độ hoàn thành > 70 VÀ điểm kiểm tra > 5**.
- **Module Cabin (kết quả)**: Import file kết quả cabin (mẫu `cabin - 13C1`) theo mã học viên; ghi nhận Đạt/Không đạt theo "Đáp ứng quy định". *(Độc lập với phân hệ đăng ký lịch cabin đã có.)*
- **Module Thực hành trong hình**: Nhập tay 2 tham số Thời gian và Quãng đường; đạt theo ngưỡng từng hạng.
- **Module DAT (thực hành trên đường)**: Import file DAT của Cục hằng ngày; cộng dồn theo **mã phiên học** (chống trùng), tính các tham số A/B/C/D/E và đánh giá Đạt/Không đạt theo ngưỡng giờ + km của từng hạng (có chuẩn hóa hạng).
- **Module Tổng hợp**: Tra cứu theo ngày–tới ngày, CCCD, họ tên, khóa, hạng, giáo viên; xuất Excel danh sách Đạt/Không đạt kèm chi tiết 4 module.
- **Cổng công khai học viên**: Nhập CCCD để tự xem tiến độ 4 module của mình.
- **Cổng giáo viên**: Đăng nhập bằng CCCD + mật khẩu (đổi được) để xem/cập nhật học viên mình phụ trách.
- **Nhật ký import**: Ghi lại ai import file gì, ngày nào, bao nhiêu dòng, bao nhiêu lỗi.

## Capabilities

### New Capabilities
- `student-training-base-data`: Import và tra cứu danh sách xe và giáo viên đào tạo.
- `student-training-course`: Quản lý khóa học, import danh sách học viên (mẫu 2) và bảng ánh xạ mã học viên ⇄ CCCD.
- `student-training-theory`: Import và đánh giá kết quả 6 môn lý thuyết theo CCCD.
- `student-training-cabin-result`: Import và đánh giá kết quả cabin theo mã học viên.
- `student-training-practice-field`: Nhập tay và đánh giá thực hành trong hình theo hạng.
- `student-training-dat`: Import phiên học DAT, cộng dồn tham số và đánh giá thực hành trên đường theo hạng.
- `student-training-summary`: Tổng hợp Đạt/Không đạt 4 module, tra cứu đa tiêu chí và xuất Excel.
- `student-training-portals`: Cổng công khai cho học viên (tra cứu CCCD) và cổng giáo viên (đăng nhập, cập nhật học viên phụ trách).

### Modified Capabilities
<!-- Không thay đổi capability hiện hữu. Phân hệ đăng ký lịch cabin (`cabin-*`) được giữ nguyên và tách biệt hoàn toàn với module cabin kết quả trong change này. -->

## Impact

- **Database**: thêm bộ bảng riêng `table_dt_*` (khóa, học viên, xe, giáo viên, lý thuyết, cabin kết quả, phiên DAT, thực hành hình, nhật ký import) qua file migration idempotent; không dùng lại `table_product`.
- **Admin**: thêm `admin/sources/daotao*.php` và bộ template `admin/templates/daotao/**`; các mục menu admin mới; tái dùng `libraries/PHPExcel` để import/xuất; tái dùng reader dò header alias + fallback + báo lỗi từng dòng (bài học từ phân hệ xăng dầu).
- **Frontend**: thêm tuyến công khai cho cổng học viên và cổng giáo viên trong `libraries/router.php`, cùng source/template và AJAX handler.
- **Dependencies**: không thêm thư viện mới; tái dùng `libraries/PHPExcel`, `libraries/class/class.PDODb.php`, `libraries/class/class.Functions.php`.
