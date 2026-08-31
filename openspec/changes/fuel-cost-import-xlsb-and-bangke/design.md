## Context

Change trước (`add-fuel-cost-management`) đã dựng phân hệ XD: bảng `table_xd_config`, `table_xd_hoadon`, `table_xd_hocvien`, `table_xd_bangke`; import Excel bằng `libraries/PHPExcel`; giáo viên định danh theo **tên chuẩn hóa** `gv_key` (các file không có CCCD giáo viên); cổng công khai `ajax/tracuu_xangdau.php` bắc cầu CCCD → hồ sơ nhân viên (`table_product` type `nhan-vien`) → tên → `gv_key`.

Dữ liệu thật (đã kiểm tra bằng PHPExcel trong container):

- **Hóa đơn** `TỔNG HỢP HÓA ĐƠN XĂNG DẦU TH05-06.xlsx` — cột: `STT | Số hóa đơn | Ngày | Thông tin bán hàng | Chi tiết | Số tiền HĐ | Biển số xe | HĐ từ trang thuế | GV | Note 1 | Note 2`. Tiền dạng số là đơn vị **nghìn** (608.75 ⇒ 608.750), dạng chuỗi có dấu phẩy là đủ VND; ngày là serial Excel.
- **Học viên** `THEO DÕI XĂNG DẦU.xlsb` — định dạng **.xlsb** (nhị phân), nhiều sheet, sheet dữ liệu tên `học viên`. Cột: `STT | HỌ VÀ TÊN | KHÓA | Ngày tháng năm sinh | Số CCCD/CC/H | NGƯỜI NỘP | PHÂN XE (giáo viên) | GHI CHÚ (nhóm BT/CK/DAT) | đã tt | Menu (ngày) | ck-2.5 | bt-3.5 | dat-2.5`.

Ràng buộc kỹ thuật quan trọng: **PHPExcel không đọc được `.xlsb`**; máy phát triển hiện không có `soffice`/`ssconvert`/`pyxlsb`. Vì vậy xlsb cần chuyển đổi bằng công cụ máy chủ và phải degrade an toàn khi thiếu.

## Goals / Non-Goals

**Goals:**
- Nhận diện đúng cột cho cả hai file thật (kể cả biến thể `PHÂN XE`, `GHI CHÚ`) và lưu/hiển thị đầy đủ thông tin.
- Import được `.xlsb` (qua chuyển đổi sang `.xlsx`), chọn đúng sheet `học viên`.
- Xuất "Bảng kê trích chi phí nhiên liệu" theo từng giáo viên đúng mẫu (kèm bảng hóa đơn chi tiết + bảng học viên + tổng cộng + chữ ký) ra Excel.
- Cổng GV: nhập CCCD (như tra cứu nhân viên) xem hóa đơn + học viên đã thanh toán của mình.

**Non-Goals:**
- Không viết trình đọc `.xlsb` thuần PHP (parser BIFF12) — quá phức tạp, rủi ro cao.
- Không nhúng LibreOffice vào image nếu chưa cần; chỉ dùng khi máy chủ đã có (degrade nếu thiếu).
- Không xuất PDF ở bản này (Excel trước; PDF có thể bổ sung sau).
- Không đổi mô hình định danh GV (vẫn theo `gv_key`).

## Decisions

### 1. Nhận diện cột linh hoạt + nhận diện nhóm theo giá trị

Mở rộng `xd_detect_header` alias/containsRules:

- **Học viên**: teacher = `phanxe` | `giaovien` | `gv` | `gvphutrach`; nhóm = `nhom` | `ghichu` (chỉ nhận khi cột chứa giá trị BT/CK/DAT); `nguoinop`; `khoa`; `hovaten`; `socccd*`; `ngaythangnamsinh`.
- **Hóa đơn**: `sohoadon`, `ngay`, `thongtinbanhang`, `chitiet`, `sotienhd`, `bienso`, `gv`.

Vì `GHI CHÚ` là tiêu đề mơ hồ, thêm bước **nhận diện cột nhóm theo dữ liệu**: nếu chưa map được `nhom`, chọn cột (trong vùng dữ liệu) có tỉ lệ ô thuộc {BT, CK, DAT} cao nhất. Ưu tiên header rõ trước, dữ liệu sau.

**Vì sao:** tiêu đề giữa các file không đồng nhất; kết hợp header + giá trị giúp nhận diện bền vững mà không đọc nhầm cột.

### 2. Lưu đủ chi tiết để dựng bảng kê

Thêm cột (idempotent) vào `table_xd_hoadon`: `thong_tin_ban_hang VARCHAR(255)`, `chi_tiet VARCHAR(50)` (đã có `bien_so`). Học viên đã có `khoa`, `nguoi_nop`, `ngaysinh`, `gv_hoten`, `gv_key`.

**Vì sao:** bảng kê mẫu cần đúng các cột hóa đơn (Thông tin bán hàng, Chi tiết, Biển số xe) và học viên (Khóa, Năm sinh, Định mức, Số tiền).

### 3. Hỗ trợ `.xlsb` bằng chuyển đổi máy chủ, degrade an toàn

Luồng khi ext = `xlsb`:
1. Lưu file tạm, gọi bộ chuyển đổi có sẵn theo thứ tự dò: `soffice --headless --convert-to xlsx` (LibreOffice) → nếu không có, thử `ssconvert` (Gnumeric) → nếu không có, thử `python3 -m pyxlsb`/script.
2. Nạp `.xlsx` kết quả bằng `PHPExcel_IOFactory::createReader('Excel2007')`.
3. Chọn sheet theo tên chứa `hoc vien`/`học viên` (chuẩn hóa không dấu); nếu không có, dùng sheet đầu.
4. Nếu **không** có công cụ chuyển đổi: `transfer()` báo lỗi rõ ("Máy chủ chưa hỗ trợ đọc .xlsb, vui lòng lưu file dưới dạng .xlsx rồi import lại.").

Bao bọc bằng timeout/`set_time_limit` và giới hạn bộ nhớ; chỉ chuyển đổi 1 lần, xóa file tạm sau khi đọc.

**Vì sao:** không có parser xlsb thuần PHP đáng tin; chuyển đổi là cách chuẩn, và degrade giữ hệ thống không vỡ khi thiếu công cụ.

**Phương án loại bỏ:** parser BIFF12 thuần PHP (phức tạp/nguy cơ); nhúng LibreOffice mặc định vào image (nặng, để tùy chọn triển khai).

### 4. Bảng kê trích chi phí nhiên liệu theo từng giáo viên (đúng mẫu)

Thay `xd_export_bangke_excel` bằng bản dựng theo template:

- Tiêu đề: `TRUNG TÂM GIÁO DỤC NGHỀ NGHIỆP BÁCH VIỆT` (lấy từ `#_setting.tenvi` nếu có), `BẢNG KÊ TRÍCH CHI PHÍ NHIÊN LIỆU - Số: ...`, `Giáo viên: <tên>`, `Ngày quyết toán: <ngày>`.
- Bảng **Nội dung** (hóa đơn của GV trong đợt): STT, Số hóa đơn, Ngày, Thông tin bán hàng, Chi tiết, Số tiền HĐ, Biển số xe → dòng **Tổng cộng** cột Số tiền HĐ.
- Bảng **Danh sách học viên** (HV được trích của GV): STT, Khóa, Họ tên học viên, CCCD/CC, Năm sinh, Định mức, Số tiền thanh toán, Nhóm → dòng **Tổng cộng** (Định mức, Số tiền).
- Dòng chữ ký: Phòng Đào tạo | Kế Toán | Giáo viên quyết toán.

Khi lọc nhiều GV: xuất **mỗi GV một sheet** trong cùng workbook (hoặc một file/GV). Chọn: nhiều sheet trong 1 workbook để tiện tải một lần. Việc ghi `ngay_thanh_toan` + khóa hóa đơn giữ nguyên logic quyết toán hiện có (đúng các HĐ đã gộp vào bảng kê).

**Vì sao:** khớp đúng ảnh mẫu và nhu cầu kế toán; gộp cả hóa đơn + học viên trong một bảng kê cho mỗi GV.

### 5. Cổng GV bằng CCCD (xác nhận)

Giữ luồng: CCCD (chuẩn hóa biến thể 11/12 số) → `table_product` type `nhan-vien` → `ten` → `gv_key` → truy vấn `table_xd_hoadon`/`table_xd_hocvien` theo `gv_key` trong khoảng ngày. Bổ sung: nếu không thấy trong hồ sơ nhân viên, thử khớp `gv_key` suy ra từ chính CCCD-đối-tên đã lưu (nếu sau này có), còn không thì báo không tìm thấy.

## Risks / Trade-offs

- [Máy chủ thiếu công cụ chuyển `.xlsb`] → Degrade: báo lỗi rõ, hướng dẫn lưu `.xlsx`. Ghi chú triển khai để cài LibreOffice nếu cần dùng xlsb.
- [File `.xlsb` rất lớn/nhiều sheet (sheet4 ~68MB)] → Chỉ chuyển đổi & đọc sheet `học viên`; đặt timeout/memory; cảnh báo nếu chuyển đổi quá lâu.
- [Tiêu đề `GHI CHÚ` mơ hồ] → Nhận diện nhóm theo giá trị BT/CK/DAT; nếu vẫn không rõ, báo lỗi cột nhóm.
- [Tên GV lệch giữa file HV, file HĐ và hồ sơ nhân viên] → Chuẩn hóa `gv_key` (mb_strtolower, bỏ dấu, bỏ "thầy/cô"); vẫn có thể lệch → cần dữ liệu tên nhất quán (rủi ro nghiệp vụ, có thể chỉnh ở admin sau).
- [Chuyển đổi làm mất định dạng số/ngày] → Sau chuyển đổi vẫn đọc raw serial/số như hiện tại (đơn vị nghìn cho số, serial cho ngày).

## Migration Plan

1. Backup DB.
2. `xd_ensure_tables()` thêm cột `thong_tin_ban_hang`, `chi_tiet` cho `table_xd_hoadon` (idempotent); cập nhật `migration_xangdau.sql`.
3. Triển khai nhận diện cột linh hoạt + đọc `.xlsb` + bảng kê mẫu trong `admin/sources/xangdau.php` và templates.
4. (Tùy chọn triển khai) Cài LibreOffice trên máy chủ để bật luồng `.xlsb`; nếu chưa, người dùng lưu `.xlsx`.
5. Smoke test với đúng 2 file thật: import HĐ (.xlsx) + HV (.xlsb) → kiểm tra hiển thị đủ cột → chạy lọc → xuất bảng kê đúng mẫu → GV tra cứu bằng CCCD.
6. Rollback: cột mới không phá dữ liệu cũ; có thể tắt nhánh xlsb/bảng kê mới nếu cần.

## Open Questions

- Máy chủ production có sẵn LibreOffice `soffice` không, hay cần bổ sung vào `Dockerfile`? (Quyết định cách bật luồng `.xlsb`.)
- Bảng kê nhiều GV: xuất mỗi GV một **sheet** trong 1 file (đề xuất) hay mỗi GV một **file** (zip)? 
- "Số" bảng kê (Số: ...) tự sinh theo `id` đợt `table_xd_bangke` hay nhập tay?
- Cột `ck-2.5 / bt-3.5 / dat-2.5` trong file HV có phải định mức theo nhóm cần nạp vào cấu hình không, hay chỉ là ghi chú? (Mặc định: bỏ qua, dùng cấu hình định mức trong admin.)
