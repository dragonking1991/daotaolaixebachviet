## Context

Phân hệ XD hiện có luồng: import hóa đơn/học viên → kế toán **kiểm tra** (`ke_toan_kiem_tra`, `ngay_kiem_tra`) → quản lý **duyệt** (`xd_duyet_giao_vien` tạo `#_xd_bangke`, set `da_quyettoan=1`, `ngay_thanh_toan`, `id_bangke`, `quan_ly_duyet=1`) → xuất bảng kê. Các màn lọc: `loc` (dự kiến), `locKiemTra` (chưa kiểm tra), `locDuyet` (đã kiểm tra, chờ duyệt), `locDaThanhToan` (đã thanh toán).

Định danh GV theo `gv_key` (tên chuẩn hóa). Định mức XD/nhóm và mức thanh toán/nhóm nằm ở `#_xd_config` qua `getXdConfig()`/`saveXdConfig()`/`xdDinhMucTheoNhom()`/`xdMucTheoNhom()` trong `libraries/xangdau_config.php`. Thuật toán trích (`xd_run_algorithm`) duyệt học viên theo thứ tự `id`, trừ dần ngân sách hóa đơn bằng **định mức XD của nhóm** học viên đó.

Quyền: `permissions.php` phân tách `xd_can_duyet()` (admin/quản lý/`xangdau_duyet`), `xd_can_kiem_tra()` (kế toán hoặc admin), `xd_can_xoa()`, `xd_can_import()`, `xd_can_config()`; export hiện gated `xd_can_duyet() || xd_can_kiem_tra()`.

Cột "Khóa" (`#_xd_hocvien.khoa`, VARCHAR) lưu hạng khóa từ file import (ví dụ `bss`, `btd`, `c1`, `c`, `ce`, và các biến thể như `B11-BSS`, `C1.05`...). Nhóm (`nhom`) là BT/CK/DAT.

## Goals / Non-Goals

**Goals:**
- Xuất Excel "Tổng hợp GV đã duyệt" (đã quyết toán) cho cả kế toán và admin.
- Hủy duyệt **theo đợt (bảng kê)**, tìm bằng cách lọc theo tên GV; hoàn tác an toàn về trạng thái chưa quyết toán.
- Nút xuất Excel (Tổng / Đã TT / Chưa TT) ở danh sách Hóa đơn XD và Học viên XD theo đúng bộ lọc đang xem.
- Admin sửa `hop_le` từng hóa đơn; xóa/sửa/import bổ sung từng bản ghi mà không phải xóa toàn bộ.
- Định mức XD theo **(nhóm CK/DAT × hạng khóa)** với fallback an toàn.

**Non-Goals:**
- Không đổi mô hình định danh GV (vẫn `gv_key`).
- Không đổi công thức "mức thanh toán/nhóm" (số tiền trả mỗi HV) — chỉ đổi **định mức chia** (số lượng) cho CK/DAT theo hạng khóa.
- Không thêm bảng DB mới; không thêm thư viện.
- Không làm sửa inline toàn bộ trường của hóa đơn/học viên (chỉ `hop_le` + xóa/append theo yêu cầu); sửa nội dung khác vẫn qua xóa dòng + import lại.

## Decisions

### 1. Export "Tổng hợp GV đã duyệt"

Thêm `xd_xuat_tong_hop_da_duyet()` (trong `export_tonghop.php`) truy vấn GV **đã quyết toán** thay vì chạy thuật toán dự kiến:

- Nguồn: `#_xd_hoadon` where `da_quyettoan = 1` join/aggregate theo `gv_key`, cùng số HV đã thanh toán (`#_xd_hocvien.ngay_thanh_toan is not null`) và tổng tiền (`sum(so_tien_thanh_toan)` của học viên đã thanh toán, hoặc `#_xd_bangke.tong_tien`).
- Bộ lọc: khoảng `ngay_thanh_toan` (paid_from/paid_to) + `ky` — trùng tham số màn `locDaThanhToan`.
- Cột: STT | Giáo viên | SL HV đã TT | Số tiền | Ngày quyết toán | Ghi chú, + dòng Tổng cộng + "Bằng chữ" + chữ ký (tái dùng bố cục `xd_xuat_tong_hop_giao_vien`).
- Route `act=xuatTongHopDaDuyet`; nút trên `loc/items_dathanhtoan_tpl.php`; quyền `xd_can_duyet() || xd_can_kiem_tra()` (cả kế toán và admin).

**Vì sao tách hàm mới:** export cũ dựa trên thuật toán dự kiến (chưa duyệt) phục vụ bước duyệt; yêu cầu là danh sách **đã duyệt** — ngữ nghĩa khác, nguồn dữ liệu khác.

### 2. Hủy duyệt theo đợt (bảng kê)

Đảo ngược đúng những gì `xd_duyet_giao_vien` đã ghi, phạm vi theo **một đợt `id_bangke`**:

1. Màn lọc GV đã duyệt cho phép **tìm theo tên GV** (`locDaThanhToan` đã có; thêm ô keyword lọc theo `gv_hoten`), liệt kê các đợt (`#_xd_bangke`) của GV kèm ngày, số HV, số tiền.
2. Hành động `act=huyDuyetDot&id_bangke=...`:
   - `update #_xd_hocvien set ngay_thanh_toan=null, id_bangke=0, quan_ly_duyet=0, so_tien_thanh_toan=0, dinh_muc=0 where id_bangke=?`
   - `update #_xd_hoadon set da_quyettoan=0, ngay_thanh_toan=null, quan_ly_duyet=0, id_bangke=0 where id_bangke=?` (giữ `ke_toan_kiem_tra` để không phải kiểm tra lại; hoặc reset theo yêu cầu — mặc định GIỮ để hủy duyệt ≠ hủy kiểm tra).
   - `delete from #_xd_bangke where id=?` (hoặc đánh dấu hủy) trong cùng transaction.
3. Sau hủy, GV quay lại danh sách "chờ duyệt"/"lọc" bình thường.

Quyền: chỉ `xd_can_duyet()` (admin/quản lý). Xác nhận (confirm) + thông báo rõ số bản ghi hoàn tác.

**Vì sao theo đợt, không theo GV:** một GV có thể có nhiều đợt; `id_bangke` là đơn vị hoàn tác chính xác, tránh gỡ nhầm đợt khác. Yêu cầu "hủy duyệt theo đợt" khớp trực tiếp.

**Rủi ro:** nếu học viên đã bị sửa sau khi duyệt → hoàn tác vẫn set null các trường thanh toán (an toàn, không xóa học viên). Không đụng tới học viên nhóm khác vì lọc theo `id_bangke`.

### 3. Xuất Excel ở HĐ XD & Học viên XD (Tổng / Đã TT / Chưa TT)

Tái dùng đúng bộ lọc của `xd_get_hoadon()` / `xd_get_hocvien()` (tách phần dựng `where/params` thành helper để export và list dùng chung), thêm tham số `scope ∈ {all, paid, unpaid}`:

- **Hóa đơn**: paid = `da_quyettoan=1`, unpaid = `da_quyettoan=0`.
- **Học viên**: paid = `ngay_thanh_toan is not null`, unpaid = `ngay_thanh_toan is null`.
- Route mới: `act=xuatHoadonExcel&scope=...`, `act=xuatHocvienExcel&scope=...`. Xuất bảng phẳng đủ cột đang hiển thị + dòng tổng số/tổng tiền.
- UI: nhóm nút dropdown "Xuất Excel" trên 2 template, giữ nguyên query lọc (hidden fields / build URL từ bộ lọc hiện hành).

Quyền: `xd_can_duyet() || xd_can_kiem_tra()` (xuất là hành động đọc; kế toán và admin đều được).

**Vì sao tách helper where:** tránh lệch logic giữa danh sách và export; DRY.

### 4. Sửa "Hợp lệ" + xóa/sửa/import bổ sung từng bản ghi

- **Đổi `hop_le`**: `act=toggleHopLeHoadon&id=...` → lật `hop_le` 0↔1, **chỉ khi `da_quyettoan=0`** (đã quyết toán thì khóa). Nút trên `hoadon/items_tpl.php` (cạnh nút kiểm tra/xóa). Quyền: `xd_can_kiem_tra()` (kế toán/admin — kiểm tra tính hợp lệ là việc kế toán) — hoặc `xd_can_duyet()`; chọn `xd_can_kiem_tra()` để kế toán cũng sửa được khi phát hiện sai.
- **Xóa từng bản ghi**: đã có `xd_delete_hoadon`/`xd_delete_hocvien` (chặn khi đã quyết toán/đã TT). Giữ nguyên, đảm bảo nút hiển thị cho admin (`xd_can_xoa()` cho nút xóa từng dòng — hiện nút xóa dòng đang hiển thị không gate; làm rõ gate theo `xd_can_xoa()`).
- **Import bổ sung (append)**: import hiện đã là **thêm mới + cập nhật theo khóa trùng**, KHÔNG xóa dữ liệu cũ → đáp ứng "import bổ sung". Xác nhận và ghi chú rõ trên trang upload (không có bước xóa toàn bộ ngầm).

**Vì sao chỉ toggle `hop_le` (không sửa inline mọi trường):** giảm bề mặt rủi ro/injection; nhu cầu chính là đánh dấu hợp lệ và loại/append từng dòng. Sửa nội dung sâu hơn vẫn theo xóa-dòng + import lại (an toàn, có kiểm soát).

### 5. Định mức XD theo (nhóm CK/DAT × hạng khóa)

Cấu hình mới trong `#_xd_config`, khóa dạng `xd_dinh_muc_<nhom>_<hang>` với `nhom ∈ {ck, dat}`, `hang ∈ {bss, btd, c1, c, ce}` — tối đa 10 khóa mới. `getXdConfig()` đọc thêm các khóa này vào một mảng con `dinh_muc_khoa[nhom][hang]`.

Chuẩn hóa hạng khóa từ `#_xd_hocvien.khoa` bằng helper `xdHangKhoa($khoa)`:
- mb_lower, bỏ khoảng trắng/ký tự phân cách; nhận diện theo thứ tự **ưu tiên chuỗi dài trước** để tránh nuốt: `bss` → `btd` → `ce` → `c1` → `c`. (ví dụ "B11-BSS" → bss; "C1.05" → c1; "CE-2" → ce; "C.03" → c.)
- Không khớp → trả `''` (dùng fallback nhóm).

`xdDinhMucTheoNhom($config, $nhom, $khoa='')` mở rộng:
- Nếu `nhom ∈ {CK, DAT}` và `xdHangKhoa($khoa)` có cấu hình `>0` → dùng định mức theo (nhóm, hạng khóa).
- Ngược lại → fallback định mức nhóm hiện tại (`dinh_muc_ck`/`dinh_muc_dat`), rồi `dinh_muc` chung.

`xd_run_algorithm` truyền thêm `$hv['khoa']` vào `xdDinhMucTheoNhom(...)`. `xd_update_hocvien_status` (cập nhật thủ công) cũng dùng định mức theo khóa để nhất quán.

Form cấu hình (`config/item_edit_tpl.php`): thêm khối "Định mức XD theo hạng khóa (CK/DAT)" — lưới nhóm×hạng (2 nhóm × 5 hạng). `xd_save_config` whitelist thêm 10 khóa mới; `saveXdConfig` allow-list mở rộng.

**Vì sao lưu trong `#_xd_config` (key-value), không thêm cột/bảng:** số khóa nhỏ, cố định, tương thích ngược, không migration schema; thiếu khóa → fallback nên an toàn triển khai dần.

**Lưu ý số lượng hạng:** yêu cầu ghi "4 hạng" nhưng liệt kê 5 giá trị (`bss, btd, c1, c, ce`). Thiết kế hỗ trợ **cả 5**; nếu nghiệp vụ chỉ cần 4, các ô thừa để trống sẽ tự fallback (không ảnh hưởng). Xem Open Questions.

## Risks / Trade-offs

- [Hạng khóa biến thể nhiều dạng] → `xdHangKhoa()` dùng ưu tiên chuỗi dài + chuẩn hóa; khớp nhầm giữa `c`/`c1`/`ce` được giảm bằng thứ tự nhận diện. Không khớp → fallback nhóm (không sai lệch tiền, chỉ dùng định mức chung).
- [Hủy duyệt sau khi dữ liệu đã thay đổi] → hoàn tác theo `id_bangke` chỉ set null các trường thanh toán, không xóa học viên/hóa đơn; an toàn, có thể duyệt lại.
- [Export theo bộ lọc lệch với danh sách] → dùng chung helper build-where để đồng nhất.
- [Toggle `hop_le` trên hóa đơn đã quyết toán] → chặn ở server (`da_quyettoan=0`) + ẩn nút trên UI.
- [Định mức theo khóa cấu hình thiếu] → fallback nhóm → không vỡ thuật toán.

## Migration Plan

1. Backup DB.
2. Triển khai code: `xangdau_config.php` (đọc/ghi + helper khóa), `config.php`/`config/item_edit_tpl.php` (form), `algorithm.php` (dùng định mức theo khóa), `export_tonghop.php` (export đã duyệt), `loc.php` (+ hủy duyệt đợt, + lọc theo tên GV), `hoadon_crud.php`/`hocvien_crud.php` (+ export scope, + toggle hợp lệ), `xangdau.php` (routes), `permissions.php` (act mapping), templates.
3. `#_xd_config` không cần migration cột; khóa định mức theo khóa được tạo khi lưu lần đầu.
4. Smoke test: (a) cấu hình định mức CK/DAT theo khóa → chạy lọc → kiểm tra số HV chọn đổi đúng; (b) duyệt 1 GV → hủy duyệt đợt → dữ liệu về chưa quyết toán; (c) export Tổng hợp đã duyệt; (d) export HĐ/HV theo 3 scope; (e) toggle hợp lệ + xóa/append từng dòng.
5. Rollback: các khóa config mới vô hại nếu bỏ; routes/nút mới có thể ẩn; hủy duyệt chỉ chạy khi bấm.

## Open Questions

- "4 hạng" vs 5 giá trị liệt kê (`bss, btd, c1, c, ce`): cần xác nhận danh sách hạng chính thức cho CK/DAT (mặc định thiết kế hỗ trợ đủ 5).
- Định mức theo khóa có áp dụng cho **cả CK và DAT** như nhau, hay chỉ một nhóm? (Thiết kế: cả hai, cấu hình độc lập.)
- Khi hủy duyệt đợt: có **reset `ke_toan_kiem_tra`** về 0 (bắt kiểm tra lại) hay giữ nguyên? (Mặc định: giữ — hủy duyệt ≠ hủy kiểm tra.)
- Export HĐ/HV: cần thêm cột nào ngoài các cột đang hiển thị trên danh sách không?
