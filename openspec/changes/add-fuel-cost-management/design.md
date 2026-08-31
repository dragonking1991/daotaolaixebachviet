## Context

Trường đã có sẵn các hạ tầng gần với tính năng này:

- **Cấu hình đơn giá payroll** (`libraries/payroll_config.php` + `admin/sources/payroll_config.php`): mẫu đọc/ghi tham số vào một bảng config với fallback mặc định. Tính năng XD sẽ tái dùng gần như nguyên bản để lưu định mức.
- **Import/khóa hóa đơn** (`admin/sources/hoadon.php` + `table_hoadon`): mẫu import Excel bằng PHPExcel, `ensure_table` tạo bảng idempotent, chống trùng theo mã hóa đơn (`UNIQUE`), chuẩn hóa header tiếng Việt không dấu.
- **Import học viên + tra cứu công khai bằng CCCD** (kỳ sát hạch `table_kysathach` → `table_product`, tra cứu qua `ajax/tracuu.php`; tra cứu nhân viên qua `ajax/tracuu_nhanvien.php`): mẫu xác thực CCCD (chuẩn hóa biến thể 11/12 số) và trả HTML.
- Quy ước chung: DB qua PDO prepared statements (`libraries/class/class.PDODb.php`), prefix `table_`, viết `#_` trong SQL; admin theo mô hình thủ tục `admin/sources/<module>.php` với `switch($act)` và template `admin/templates/<module>/<act>/items_tpl.php`; helper `libraries/class/class.Functions.php` (`transfer`, `pagination`...).

Điểm khác biệt cốt lõi so với các module cũ: cần một **thuật toán ghép nối** hóa đơn ↔ học viên theo từng GV và một **quy trình quyết toán** ghi ngày thanh toán + khóa dữ liệu, chống trùng CCCD trên toàn hệ thống và chống import đè hóa đơn đã quyết toán.

## Goals / Non-Goals

**Goals:**
- Admin cấu hình định mức XD/HV, mức BT, mức CK/DAT (chỉnh sửa được, có mặc định).
- Import hóa đơn XD theo kỳ, chống trùng theo Mã HĐ + Ngày HĐ và theo kỳ; khóa hóa đơn đã quyết toán.
- Import học viên theo nhóm BT/CK/DAT gắn GV; chặn cả file nếu có CCCD trùng, báo lỗi rõ theo dòng.
- Chạy thuật toán lọc thanh toán theo GV: $N = \lfloor S_{HĐ} / \text{định mức} \rfloor$, chọn $N$ HV chưa thanh toán đầu tiên, gán tiền theo nhóm.
- Xuất bảng kê trích chi phí; auto ghi `ngay_thanh_toan` và khóa HV/HĐ đã trích.
- GV đăng nhập bằng CCCD, lọc theo khoảng ngày, xem hóa đơn XD và HV đã thanh toán của mình.

**Non-Goals:**
- Không tích hợp phần mềm hóa đơn điện tử / API thuế; hóa đơn nạp thủ công qua Excel.
- Không quản lý dòng tiền/ngân hàng thực tế; chỉ lập bảng kê và đánh dấu đã thanh toán.
- Không gửi email/SMS thông báo cho GV.
- Không phân quyền chi tiết từng GV bằng mật khẩu (dùng CCCD như mô hình tra cứu công khai hiện hữu).
- Không đa ngôn ngữ cho màn hình mới (chỉ tiếng Việt).

## Decisions

### 1. Mô hình dữ liệu: 4 bảng riêng cho phân hệ XD

Tạo bảng mới thay vì nhồi vào `table_product`/`table_hoadon` để tách nghiệp vụ và ràng buộc rõ ràng:

- `table_xd_config`: lưu tham số dạng key/value (`xd_dinh_muc`, `xd_muc_bt`, `xd_muc_ck`, `xd_muc_dat`) — theo đúng mẫu `table_payroll_config`.
- `table_xd_hoadon`: hóa đơn XD của GV — `id`, `gv_cccd`, `gv_hoten`, `ma_hoa_don`, `ngay_hoa_don` (DATE), `tong_tien` (DECIMAL(18,2)), `ky` (chuỗi kỳ tổng hợp, ví dụ `T5`, `T6`), `da_quyettoan` (TINYINT), `id_bangke` (khóa liên kết đợt quyết toán), audit (`ngaytao`, `user_tao`).
- `table_xd_hocvien`: học viên XD — `id`, `ho_ten`, `cccd`, `ngaysinh` (nullable), `nhom` (ENUM `BT`/`CK`/`DAT`), `gv_cccd`, `gv_hoten`, `dinh_muc` (chốt tại thời điểm quyết toán), `so_tien_thanh_toan`, `ngay_thanh_toan` (DATE, NULL = chưa TT), `id_bangke`, audit.
- `table_xd_bangke`: mỗi đợt xuất bảng kê — `id`, `ngay_lap` (DATE), `ky`, `tong_hocvien`, `tong_tien`, `user_tao`, `ngaytao`.

**Vì sao:** nghiệp vụ XD độc lập với sản phẩm/kỳ sát hạch; tách bảng giúp đặt UNIQUE/khóa quyết toán mà không ảnh hưởng dữ liệu cũ.

### 2. Định danh Giáo viên bằng CCCD (không bảng GV riêng)

GV được định danh bằng **CCCD** lưu trực tiếp trên cả `table_xd_hoadon` và `table_xd_hocvien` (`gv_cccd`, kèm `gv_hoten` để hiển thị). Thuật toán và cổng tra cứu ghép nối theo `gv_cccd`.

**Vì sao:** đơn giản, tự chứa, nhất quán với mô hình tra cứu bằng CCCD hiện hữu; không phụ thuộc bảng nhân sự payroll. **Trade-off:** dữ liệu GV lặp trên nhiều dòng — chấp nhận vì nguồn dữ liệu là Excel do Kế toán nạp và tên GV chỉ để hiển thị.

### 3. Ràng buộc chống trùng

- **Học viên**: `UNIQUE (cccd)` trên toàn `table_xd_hocvien`. Trước khi lưu file import, đọc toàn bộ dòng, chuẩn hóa CCCD, phát hiện trùng **trong file** và **với DB**; nếu có bất kỳ trùng nào → **rollback/không ghi dòng nào**, trả danh sách lỗi theo dòng (ví dụ: `Dòng 15: CCCD 0123...xx đã tồn tại trên hệ thống`).
- **Hóa đơn**: `UNIQUE (ma_hoa_don, ngay_hoa_don)`; đồng thời chặn import khi kỳ (`ky`) đã tồn tại dữ liệu (cảnh báo "đã import kỳ T5/T6") hoặc khi hóa đơn trùng đã có `da_quyettoan = 1` (khóa, không đè).

**Vì sao:** yêu cầu nghiệp vụ bắt buộc CCCD duy nhất và không import đè kỳ/hóa đơn đã quyết toán; kiểm tra toàn bộ trước khi ghi để đảm bảo tính toàn vẹn "all-or-nothing".

### 4. Thuật toán lọc thanh toán (core logic)

Chạy trong một giao dịch, theo từng GV (`gv_cccd`):

1. $S_{HĐ}$ = tổng `tong_tien` các hóa đơn **hợp lệ, chưa quyết toán** (`da_quyettoan = 0`) của GV trong kỳ/khoảng xét.
2. $N = \lfloor S_{HĐ} / \text{định mức} \rfloor$ (định mức lấy từ config, mặc định `3.500.000`).
3. Chọn $N$ học viên **chưa thanh toán** (`ngay_thanh_toan IS NULL`) đầu tiên của GV (sắp theo `id` tăng dần để ổn định thứ tự).
4. Gán `so_tien_thanh_toan`: nhóm `BT` = mức BT (mặc định `1.200.000`); nhóm `CK`/`DAT` = mức tương ứng (mặc định `0`). Gán `dinh_muc` = định mức hiện hành.

Kết quả là bản xem trước (preview) trước khi xuất bảng kê; chưa ghi `ngay_thanh_toan` ở bước này.

**Vì sao:** khớp đúng ví dụ nghiệp vụ ($S_{HĐ}=13.5M \Rightarrow N=3$); tách preview khỏi commit để admin kiểm tra trước khi khóa.

### 5. Xuất bảng kê + auto cập nhật ngày thanh toán (quyết toán)

Khi admin bấm "Xuất bảng kê trích chi phí nhiên liệu" / "Xác nhận thanh toán", trong một giao dịch:
1. Tạo bản ghi `table_xd_bangke` (ngày lập = ngày thực hiện).
2. Với các HV được chọn ở bước lọc: ghi `ngay_thanh_toan = ngày thực hiện`, `id_bangke`, chốt `dinh_muc` và `so_tien_thanh_toan`.
3. Đánh dấu các hóa đơn đã dùng: `da_quyettoan = 1`, `id_bangke`.
4. Sinh file Excel (PHPExcel `Excel2007`) theo template mẫu; có thể kèm xuất PDF (tùy chọn, dùng thư viện sẵn có).

HV đã có `ngay_thanh_toan` sẽ bị loại khỏi các đợt lọc sau (điều kiện `ngay_thanh_toan IS NULL`).

**Vì sao:** đảm bảo idempotent và không trích trùng; ghi ngày thanh toán đồng thời với xuất bảng kê đúng yêu cầu auto-update.

### 6. Cổng tra cứu GV bằng CCCD

GV nhập CCCD (+ chọn `Từ ngày`–`Đến ngày`). Hệ thống chuẩn hóa CCCD (biến thể 11/12 số như `ajax/tracuu.php`), truy vấn:
- Bảng hóa đơn: `table_xd_hoadon` theo `gv_cccd` và `ngay_hoa_don` trong khoảng.
- Bảng học viên đã thanh toán: `table_xd_hocvien` theo `gv_cccd`, `ngay_thanh_toan` trong khoảng (chỉ HV đã thanh toán).

Trả về hai bảng HTML; nếu CCCD không khớp GV nào → thông báo không tìm thấy.

**Trade-off:** CCCD là định danh yếu, nhưng nhất quán với mô hình tra cứu công khai; chỉ lộ dữ liệu chi phí của chính GV.

### 7. Import/Xuất Excel bằng PHPExcel

- Import (cả `.xlsx` và `.xls`): dùng `PHPExcel_IOFactory::createReader('Excel2007'/'Excel5')`, đọc từ dòng 2, chuẩn hóa header tiếng Việt không dấu như `hoadon.php`.
- Cột hóa đơn (đề xuất): Mã HĐ, Ngày HĐ, Tổng tiền, CCCD GV, Tên GV, Kỳ.
- Cột học viên (đề xuất): Họ tên, Ngày sinh, CCCD, Nhóm (BT/CK/DAT), CCCD GV, Tên GV.
- Xuất bảng kê: `createWriter(..., 'Excel2007')` theo template mẫu (STT, Họ tên, CCCD, Nhóm, GV, Định mức, Số tiền, Ngày TT).

## Risks / Trade-offs

- [Import trùng CCCD làm hỏng dữ liệu] → UNIQUE `(cccd)` + kiểm tra toàn bộ file trước khi ghi, all-or-nothing, báo lỗi theo dòng.
- [Import đè kỳ/hóa đơn đã quyết toán] → UNIQUE `(ma_hoa_don, ngay_hoa_don)` + chặn theo `ky` đã tồn tại và cờ `da_quyettoan`.
- [Trích trùng học viên qua nhiều đợt] → điều kiện `ngay_thanh_toan IS NULL` khi lọc; ghi ngày TT trong giao dịch khi xuất bảng kê.
- [Thứ tự chọn N học viên không ổn định] → sắp theo `id` tăng dần, cố định và có thể tái lập.
- [Hiệu năng lọc 1.000 HV < 3s] → index theo `gv_cccd`, `ngay_thanh_toan`, `ngay_hoa_don`; gom nhóm bằng SQL thay vì vòng lặp PHP nặng.
- [`.xlsx` sinh sai engine đọc rỗng] → theo ghi chú repo, validate số dòng đọc được và báo lỗi rõ nếu 0 dòng.
- [CCCD là xác thực yếu ở cổng GV] → chỉ lộ dữ liệu chi phí của chính GV; chấp nhận như rủi ro nghiệp vụ đã biết.

## Migration Plan

1. Backup DB (theo quy ước thư mục `backups/`).
2. Tạo `migration_xangdau.sql` (idempotent) tạo `table_xd_config`, `table_xd_hoadon`, `table_xd_hocvien`, `table_xd_bangke` và các UNIQUE/index; hàm `*_ensure_table` trong source cũng tạo bảng nếu thiếu (như `hoadon.php`).
3. Thêm `libraries/xangdau_config.php` với mặc định (`3.500.000` / `1.200.000` / `0` / `0`).
4. Triển khai `admin/sources/xangdau.php` + template + mục menu + quyền.
5. Triển khai cổng GV (frontend + route công khai).
6. Smoke test: cấu hình định mức → import hóa đơn → import học viên (thử CCCD trùng để thấy chặn) → chạy lọc → xuất bảng kê → kiểm tra ngày TT được ghi và HV bị khóa khỏi đợt sau → GV tra cứu bằng CCCD.
7. Rollback: gỡ route/menu; bảng mới có thể giữ lại (không phá dữ liệu cũ) hoặc drop nếu cần hoàn toàn.

## Open Questions

- "Kỳ tổng hợp" (`ky`) nhập tay trên form import hay đọc từ một cột trong Excel? (Đề xuất: đọc từ cột `Kỳ`, cho ghi đè bằng ô chọn kỳ trên form.)
- Khi $N$ lớn hơn số HV chưa thanh toán hiện có của GV thì lấy hết số HV còn lại — đúng như kỳ vọng? (Đề xuất: đúng, lấy tối đa số HV sẵn có.)
- Bảng kê có bắt buộc xuất PDF không hay Excel là đủ? (Đề xuất: Excel bắt buộc; PDF tùy chọn giai đoạn sau.)
- Định mức/mức BT dùng giá trị tại thời điểm quyết toán được chốt vào từng HV (`dinh_muc`, `so_tien_thanh_toan`) để lịch sử không đổi khi admin sửa config sau — xác nhận cách này.
