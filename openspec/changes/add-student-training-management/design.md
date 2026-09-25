## Context

Đây là phân hệ lớn nhất từ trước tới nay của dự án, nhưng hạ tầng đã có sẵn từ các phân hệ tương tự:

- **DB**: PDO prepared statements (`libraries/class/class.PDODb.php`), prefix `table_`, viết `#_` trong câu SQL.
- **Import/Xuất Excel**: `libraries/PHPExcel` (`PHPExcel_IOFactory::createReader('Excel2007')`). Change này **chỉ nhận `.xlsx`** (câu hỏi 8).
- **Admin thủ tục**: `admin/sources/<module>.php` với `switch($act)` + template `admin/templates/<module>/<act>/items_tpl.php`; phân quyền trong `libraries/requick.php`; menu `admin/templates/layout/menu.php`.
- **Frontend/tra cứu công khai**: `libraries/router.php` ánh xạ `com` → source + template; AJAX trong `ajax/*.php` include `ajax_config.php`; chuẩn hóa CCCD 11/12 số như `ajax/tracuu.php`.
- **Bài học import (phân hệ xăng dầu)**: `strtolower()` KHÔNG multibyte-safe → luôn `mb_strtolower(...,'UTF-8')` trước khi bỏ dấu; dò header theo **alias + fallback substring**, không bao giờ để header khớp một phần rồi tự nhảy về thứ tự cột cố định; đọc lỗi **từng dòng** chứ không chỉ banner chung; giáo viên định danh theo **tên chuẩn hóa** (`gv_key`) vì các file không có CCCD giáo viên.

Điểm khác biệt cốt lõi: dữ liệu đến từ **nhiều nguồn với khóa nối khác nhau** và cần **tính toán Đạt/Không đạt theo quy định pháp lý của từng hạng**, nên cần bảng ánh xạ trung tâm và một bộ quy tắc rõ ràng.

## Goals / Non-Goals

**Goals:**
- Import xe, giáo viên, khóa, học viên, 6 môn lý thuyết, cabin kết quả, DAT; nhập tay thực hành trong hình.
- Tính Đạt/Không đạt tự động cho từng module theo ngưỡng của từng hạng.
- Tổng hợp + tra cứu đa tiêu chí + xuất Excel Đạt/Không đạt.
- Cổng học viên (tra cứu CCCD) và cổng giáo viên (đăng nhập, cập nhật).
- Import idempotent, chống trùng, có nhật ký.

**Non-Goals:**
- Không thay đổi/di trú phân hệ đăng ký lịch cabin hiện có (`table_cabin_*`).
- Không gửi email/SMS, không thanh toán, không sinh QR.
- Không đa ngôn ngữ cho màn hình mới (chỉ tiếng Việt).
- Không tự động đồng bộ trực tiếp với hệ thống của Cục (chỉ import file Cục xuất ra).

## Decisions

### 1. Bảng riêng `table_dt_*`, không dùng lại `table_product`

DAT lưu **từng phiên học** và lý thuyết lưu **kết quả từng môn** — quá phức tạp để nhét vào một dòng `table_product`. Dùng bộ bảng chuyên biệt:

```
table_dt_khoa           id, ma_khoa (UNIQUE), ten_khoa, hang, ngay_khaigiang,
                        ngay_manhoa, he_daotao, ngaytao, user_tao, hienthi
table_dt_hocvien        id, id_khoa, ma_hv, cccd, hoten, ngaysinh, hang,
                        gv_key, gv_hoten, nguoi_gioithieu, he_daotao, ngaytao
                        UNIQUE(cccd, id_khoa); INDEX(ma_hv)
table_dt_xe             id, bien_so (UNIQUE), hang_xe, hang_dt, so_khung, so_may,
                        loai_xe, nhan_hieu, gv_key, gv_hoten, ...
table_dt_giaovien       id, cccd (UNIQUE), hoten, gv_key, hang_gplx,
                        hang_daotao_phep, sdt, dia_chi, matkhau, ...
table_dt_lythuyet       id, id_khoa, cccd, mon (enum 6), tien_do, diem_kt, dat
                        UNIQUE(id_khoa, cccd, mon)
table_dt_cabin_kq       id, id_khoa, ma_hv, cccd, tong_thoigian, so_noidung, dat
                        UNIQUE(id_khoa, ma_hv)
table_dt_dat_phien      id, ma_phien (UNIQUE), ma_hv, cccd, id_khoa, hang,
                        tg_batdau, tg_ketthuc, gio_thuchanh, km, la_xe_tudong,
                        gio_dem, bien_so, gv_hoten, trang_thai, ngay_hoc
table_dt_thuchanh_hinh  id, id_hocvien, cccd, id_khoa, gio, km, nguoi_nhap, ngaytao
table_dt_import_log     id, module, filename, so_dong, so_loi, user, ngaytao
```

Module Tổng hợp **tính on-the-fly** bằng JOIN, không lưu bảng tổng hợp riêng (tránh dữ liệu lệch).

### 2. Khóa nối: ánh xạ mã HV ⇄ CCCD tập trung (câu hỏi 1)

`table_dt_hocvien` là **bảng cầu nối** duy nhất, chứa **cả `ma_hv` (mã trung tâm) và `cccd`**. File mẫu 2 phải có cả hai. Từ đó:
- Lý thuyết (khóa = **CCCD**, cột "Mã đăng nhập") → join `dt_hocvien.cccd`.
- Cabin & DAT (khóa = **mã học viên**, cột "Mã học viên") → join `dt_hocvien.ma_hv`.
- Khi import một module mà không tìm được học viên khớp trong khóa → **báo dòng lỗi cụ thể** (không tạo học viên ngầm).

### 3. Chuẩn hóa hạng dùng chung (câu hỏi 5)

Một helper `dt_norm_hang($raw)` trả về mã chuẩn dùng cho cả xe lẫn DAT:

| Đầu vào | Chuẩn hóa |
|---|---|
| B số cơ khí / B số sàn / "B1" | `B1` |
| B số tự động / "B11" | `B11` |
| C1 | `C1` |
| C, "B lên C" | `C` |
| CE, "C lên CE" | `CE` |

### 4. Đánh giá Lý thuyết (câu hỏi 2, 3)

- Đọc cột **cố định theo tên tiêu đề** (không dựa "ô 1 tô vàng"): "Tiến độ hoàn thành" và "Điểm kiểm tra".
- **Đạt** khi `tien_do > 70` **VÀ** `diem_kt > 5`. Bỏ qua cột "Trạng thái" sẵn có trong file.
- Học viên đạt lý thuyết tổng khi **cả 6 môn** đều đạt.

### 5. Thuật toán DAT (câu hỏi 4)

Import từng phiên vào `table_dt_dat_phien`, **UNIQUE theo `ma_phien`**. Re-import: chỉ chèn phiên **chưa có**, báo & xác nhận số phiên trùng bị bỏ qua. Với mỗi phiên tính:

- `gio_thuchanh` = Thời gian thực hành (giờ thập phân trong file; hiển thị quy đổi giờ:phút, vd `1.15 → 1h09p`).
- `gio_dem` (B) = phần thời gian phiên rơi vào khung **18h–5h sáng**, tính theo phút của **từng phiên** rồi cộng tổng (vd phiên 17h–18h15 → B = 15 phút).
- `la_xe_tudong` = true nếu biển số xe thuộc xe số tự động → giờ này cộng vào **C (giờ tự động)**; ngược lại cộng vào **D (giờ số sàn)**.
- `km` (E) = quãng đường (vd `17.235 → 17km 235m`).

**Tổng hợp theo học viên** (cộng dồn mọi phiên khả dụng):
- `A` = tổng giờ thực hành = C + D.
- `B` = tổng giờ đêm; `C` = tổng giờ tự động; `D` = tổng giờ số sàn; `E` = tổng km.

**Ngưỡng Đạt theo hạng** (điều kiện bắt buộc mọi hạng B: `B(đêm) >= 1` giờ):

| Hạng | Giờ (A) | Điều kiện thêm | Quãng đường (E) |
|---|---|---|---|
| B11 (tự động) | A ≥ 12 | B ≥ 1 | ≥ 710 km |
| B1 (cơ khí) | A ≥ 20 | B ≥ 1, C ≥ 1, D ≥ 19 | ≥ 810 km |
| C1 | A ≥ 24 | B ≥ 1, C ≥ 1, D ≥ 23 | ≥ 825 km |
| C | A ≥ 5 | B ≥ 1 | ≥ 210 km |
| CE | A ≥ 5 | B ≥ 1 | ≥ 210 km |

Đạt DAT = thỏa **cả** điều kiện giờ **và** quãng đường của hạng tương ứng.

### 6. Ngưỡng Thực hành trong hình (câu hỏi 7)

Nhập tay 2 tham số (Thời gian giờ, Quãng đường km); đạt theo hạng:

| Hạng | Quãng đường | Thời gian |
|---|---|---|
| B tự động (B11) | ≥ 120 km | > 34 h |
| B số sàn (B1) | ≥ 120 km | > 34 h |
| C1 | ≥ 113 km | > 35 h |
| C | ≥ 15 km | > 7 h |
| CE | ≥ 15 km | > 7 h |

### 7. Định danh giáo viên theo tên chuẩn hóa

File xe và file DAT liên kết giáo viên **theo tên** (không có CCCD giáo viên); file giáo viên thì có CCCD. Tái dùng pattern `gv_key` (mb_lower + bỏ dấu + bỏ "thầy/cô" + gộp khoảng trắng) để nối. Cổng giáo viên đăng nhập bằng **CCCD** (có trong `table_dt_giaovien`) + mật khẩu.

### 8. Cổng công khai

- **Học viên**: nhập CCCD (chuẩn hóa 11/12 số) → hiển thị tiến độ 4 module + tổng kết đủ/chưa đủ điều kiện kiểm tra. Chỉ đọc.
- **Giáo viên**: đăng nhập CCCD + mật khẩu (đổi được, hash như `table_user`); xem danh sách học viên mình phụ trách (nối theo `gv_key`), và **nhập/cập nhật thực hành trong hình** cho học viên của mình.

## Risks / Trade-offs

- **Vị trí cột file Cục/phần mềm bên thứ ba đổi**: giảm rủi ro bằng dò header theo alias + fallback substring + báo lỗi từng dòng; ghi header đã khớp vào flash message để dễ chẩn đoán.
- **File DAT lớn**: chỉ nhận `.xlsx`; vẫn early-exit sau ~50 dòng trống và cảnh báo nếu vượt ngưỡng dòng.
- **Sai lệch mã HV ⇄ CCCD** nếu file mẫu 2 thiếu một trong hai: bắt buộc kiểm tra và báo lỗi ngay khi import danh sách học viên.
- **Quy tắc pháp lý có thể đổi theo thời gian**: đặt ngưỡng trong một nơi cấu hình tập trung (hằng số/bảng config) để dễ chỉnh.

## Migration Plan

1. Backup DB.
2. Chạy `migration_daotao.sql` (idempotent, `CREATE TABLE IF NOT EXISTS`, thêm cột phòng thủ như các migration cũ).
3. Không đụng dữ liệu `table_product` / `table_cabin_*` hiện có.

## Open Questions

- Mã "Hạng đào tạo được phép" của giáo viên (B=2, C1=3, C=4, D1=5, E=6) có cần dùng để chặn phân công xe/giáo viên sai hạng không, hay chỉ lưu hiển thị?
- Cabin kết quả: ngoài "Đáp ứng quy định" có ngưỡng số giờ / số nội dung tối thiểu cần tự kiểm tra lại không, hay tin theo cột kết quả trong file?
