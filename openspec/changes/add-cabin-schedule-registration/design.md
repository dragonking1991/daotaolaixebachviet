## Context

Trường đã có quy trình "kỳ sát hạch": admin tạo kỳ (`table_kysathach`), import học viên từ Excel vào `table_product` (gắn `id_kysathach`, `type` chuyên biệt), học viên tra cứu công khai bằng CCCD qua `ajax/tracuu.php`. Tính năng đăng ký lịch học cabin tái dùng gần như nguyên bản hạ tầng này:

- DB qua PDO prepared statements (`libraries/class/class.PDODb.php`), prefix `table_`, gọi `#_` trong câu SQL.
- Import/xuất Excel bằng `libraries/PHPExcel` (`PHPExcel_IOFactory::createReader('Excel2007'/'Excel5')`).
- Admin theo mô hình thủ tục: `admin/sources/<module>.php` với `switch($act)` và template trong `admin/templates/<module>/<act>/items_tpl.php`.
- Frontend qua `libraries/router.php` ánh xạ `com` → source + template; AJAX trả HTML.
- Helper `libraries/class/class.Functions.php` (`changeTitle`, `transfer`, `pagination`...).

Điểm khác biệt cốt lõi so với kỳ sát hạch: học viên không chỉ tra cứu mà còn **ghi** dữ liệu (đăng ký ca), nên cần bảng đăng ký riêng, ràng buộc sức chứa mỗi ca, kiểm soát thời gian đăng ký và chống đặt trùng/đua điều kiện (race condition).

## Goals / Non-Goals

**Goals:**
- Admin tạo/sửa/xóa khóa học cabin với thời gian bắt đầu, kết thúc đăng ký và sức chứa mỗi ca.
- Admin import danh sách học viên từ Excel theo mẫu (giống kỳ sát hạch) và xem đầy đủ thông tin.
- Admin xem danh sách đăng ký theo ngày/ca và xuất Excel.
- Học viên xác thực bằng CCCD, xem lưới ca theo khung giờ cố định, đăng ký ca trống và chốt lịch.
- Ca đầy hiển thị block; ngoài thời gian đăng ký thì khóa thao tác.

**Non-Goals:**
- Không gửi email/SMS nhắc lịch.
- Không có thanh toán, không sinh QR.
- Không quản lý phòng/máy cabin vật lý ngoài khái niệm "sức chứa mỗi ca".
- Không hỗ trợ học viên tự hủy đăng ký ở bản đầu (việc hủy do văn phòng xử lý).
- Không đa ngôn ngữ cho màn hình mới (chỉ tiếng Việt).

## Decisions

### 1. Mô hình dữ liệu: khóa học + học viên tái dùng `table_product`, đăng ký dùng bảng riêng

- `table_cabin_khoahoc`: thông tin khóa (tên, `ngay_batdau`, `ngay_ketthuc`, `suc_chua_ca` mặc định 3, cờ hiển thị, audit).
- Học viên: tái dùng `table_product` với `type = 'cabin'` và cột liên kết `id_cabin_khoahoc` (giống `id_kysathach`), tận dụng sẵn cột `tenvi`, `cccd`, `ngaysinh`, `hang`. Tránh tạo bảng học viên mới và tái dùng luôn logic import.
- `table_cabin_dangky`: mỗi dòng là một học viên đặt một ca cụ thể — `id_khoahoc`, `id_hocvien` (id của `table_product`), `cccd`, `ngay_hoc` (DATE), `ca` (1–4), `gio_b_d`/`gio_kt` (chuỗi giờ hiển thị), `trang_thai`, `ngaytao`.

**Vì sao:** giữ nhất quán với kỳ sát hạch (ít rủi ro, tái dùng import/hiển thị), tách bảng đăng ký để mô hình hóa quan hệ nhiều-ca/học-viên và ràng buộc sức chứa rõ ràng.

**Phương án thay thế đã cân nhắc:** tạo bảng học viên cabin riêng — bị loại vì trùng lặp logic import và lệch khỏi quy ước hiện có.

### 2. Khung giờ cố định trong code, sức chứa cấu hình theo khóa

Bảng giờ là quy tắc cố định nên định nghĩa trong PHP (mảng cấu hình), không lưu DB:
- T2–T6: Ca1 `08:00–10:00`, Ca2 `10:00–12:00`, Ca3 `12:00–14:00`, Ca4 `14:00–16:00`.
- T7: chỉ Ca1, Ca2 (sáng). Chủ nhật: không có ca.
Số lịch tối đa mỗi (khóa, ngày, ca) = `suc_chua_ca` của khóa (mặc định 3). Một ca được coi là **đầy/block** khi số đăng ký đạt `suc_chua_ca`.

**Vì sao:** khung giờ ổn định, ít thay đổi; đưa vào code giúp lưới hiển thị nhất quán và đơn giản. Sức chứa để ở DB vì có thể khác nhau theo khóa.

### 3. Phạm vi ngày của lưới = khoảng thời gian khóa, hiển thị theo tuần

Lưới chỉ hiện các ngày trong `[ngay_batdau, ngay_ketthuc]` (loại Chủ nhật). Điều hướng theo tuần như ảnh tham chiếu nhưng giới hạn trong khoảng khóa.

### 4. Xác thực học viên bằng CCCD (không mật khẩu)

Học viên nhập CCCD; hệ thống tìm bản ghi `table_product type='cabin'` thuộc khóa với `cccd` (chuẩn hóa biến thể 11/12 số như `ajax/tracuu.php`). Khớp thì mở UI đăng ký cho đúng học viên đó. Không CCCD khớp → thông báo không tìm thấy, gợi ý liên hệ văn phòng.

**Trade-off:** CCCD là định danh yếu, nhưng nhất quán với mô hình tra cứu công khai hiện hữu và không lộ dữ liệu nhạy cảm (chỉ lịch ca). Chấp nhận như rủi ro nghiệp vụ đã biết.

### 5. Cấu hình thời gian đăng ký

Đăng ký mở khi `now <= ngay_ketthuc` (kết thúc tính hết ngày). Sau thời điểm này, AJAX đăng ký bị từ chối phía server và UI chuyển read-only kèm ghi chú liên hệ văn phòng. Kiểm tra ở cả client (ẩn nút) và server (bắt buộc) để tránh bypass.

### 6. Chống đặt trùng / đua điều kiện khi chốt ca

Khi học viên bấm đăng ký, xử lý trong giao dịch:
1. Khóa/đếm số đăng ký hiện tại của `(id_khoahoc, ngay_hoc, ca)` (dùng `SELECT ... FOR UPDATE` trong transaction trên InnoDB).
2. Từ chối nếu đã đạt `suc_chua_ca`, nếu học viên đã đặt đúng ca đó, hoặc nếu ngoài thời gian/ngoài khung giờ hợp lệ.
3. Chèn bản ghi đăng ký rồi commit.
Thêm UNIQUE index `(id_khoahoc, id_hocvien, ngay_hoc, ca)` để chặn double-submit, và index `(id_khoahoc, ngay_hoc, ca)` để đếm nhanh.

**Vì sao:** nhiều học viên có thể tranh cùng ô cuối cùng; transaction + ràng buộc unique đảm bảo không vượt sức chứa.

### 7. Import/Xuất Excel bằng PHPExcel theo mẫu kỳ sát hạch

- Import: đọc sheet 0 từ dòng 2; cột A STT, B Họ tên, C Ngày sinh, D CCCD, E Hạng (theo mẫu hiện có, bỏ phần QR ảnh). Chèn/cập nhật `table_product` theo `(cccd, type='cabin', id_cabin_khoahoc)`.
- Xuất: `PHPExcel_IOFactory::createWriter(..., 'Excel2007')` xuất danh sách đăng ký gồm Họ tên, CCCD, Hạng, Ngày học, Ca, Giờ.

## Risks / Trade-offs

- [Đua điều kiện vượt sức chứa] → Dùng transaction `FOR UPDATE` + UNIQUE index `(khóa, học viên, ngày, ca)`.
- [Bypass thời gian/đầy ca từ client] → Bắt buộc kiểm tra lại toàn bộ điều kiện ở server trước khi insert.
- [CCCD là xác thực yếu, có thể xem/đặt lịch hộ người khác] → Chỉ lộ dữ liệu lịch ca (không nhạy cảm); chấp nhận như rủi ro nghiệp vụ, văn phòng có thể chỉnh sửa.
- [`.xlsx` sinh sai engine đọc rỗng] → Theo ghi chú repo, ưu tiên file OOXML hợp lệ; validate số dòng đọc được và báo lỗi rõ nếu 0 dòng.
- [Không cho học viên tự hủy] → Văn phòng xử lý hủy/sửa ở admin; ghi rõ trong ghi chú hướng dẫn.

## Migration Plan

1. Backup DB (theo quy ước `backups/`).
2. Chạy migration tạo `table_cabin_khoahoc`, `table_cabin_dangky` và thêm cột `id_cabin_khoahoc` vào `table_product` nếu thiếu (idempotent, theo mẫu `migration_*.sql`).
3. Thêm `cabin` vào cấu hình type và menu admin.
4. Triển khai mã admin + frontend + ajax + route.
5. Smoke test: tạo khóa → import Excel → đăng ký công khai bằng CCCD → kiểm tra block khi đầy → xuất Excel.
6. Rollback: gỡ route/menu; bảng mới có thể giữ lại (không phá dữ liệu cũ) hoặc drop nếu cần hoàn toàn.

## Open Questions

- Mỗi học viên được đăng ký tối đa bao nhiêu ca trong một khóa? (Mặc định đề xuất: không giới hạn tổng, nhưng không trùng cùng một ô ca.)
- Sức chứa mỗi ca có cần khác nhau theo từng ngày không, hay cố định cho cả khóa? (Đề xuất: cố định theo khóa ở bản đầu.)
