## 1. Định mức XD theo hạng khóa (CK/DAT)

- [x] 1.1 `libraries/xangdau_config.php`: mở rộng danh sách khóa cấu hình + đọc `xd_dinh_muc_<ck|dat>_<bss|btd|c1|c|ce>` trong `getXdConfig()` (trả thêm `dinh_muc_khoa[nhom][hang]`)
- [x] 1.2 Mở rộng allow-list trong `saveXdConfig()` cho 10 khóa mới
- [x] 1.3 Thêm helper `xdHangKhoa($khoa)`: chuẩn hóa + nhận diện hạng (ưu tiên chuỗi dài: bss→btd→ce→c1→c), không khớp trả `''`
- [x] 1.4 Mở rộng `xdDinhMucTheoNhom($config, $nhom, $khoa='')`: nếu nhóm CK/DAT và có định mức theo (nhóm, hạng khóa)>0 thì dùng; ngược lại fallback định mức nhóm → định mức chung
- [x] 1.5 `admin/sources/xangdau/config.php`: `xd_save_config()` whitelist thêm 10 khóa; parse số như các khóa hiện có
- [x] 1.6 `admin/sources/xangdau/algorithm.php`: truyền `$hv['khoa']` vào `xdDinhMucTheoNhom(...)`
- [x] 1.7 `admin/sources/xangdau/hocvien_crud.php` `xd_update_hocvien_status()`: dùng định mức theo (nhóm, khóa) khi cập nhật thủ công (nhất quán thuật toán)
- [x] 1.8 `admin/templates/xangdau/config/item_edit_tpl.php`: thêm khối "Định mức XD theo hạng khóa (CK/DAT)" — lưới 2 nhóm × 5 hạng, money-input, tên field `data[xd_dinh_muc_<nhom>_<hang>]`

## 2. Hủy duyệt theo đợt + lọc GV đã duyệt theo tên

- [x] 2.1 `admin/sources/xangdau/loc.php` `xd_loc_da_thanh_toan()`: thêm ô lọc theo tên GV (`keyword` → `gv_hoten like`) và trả về danh sách **đợt** (`#_xd_bangke`) theo GV (id, ngay_lap, ky, tong_hocvien, tong_tien)
- [x] 2.2 Thêm hàm hủy duyệt: `xd_huy_duyet_giao_vien()` — transaction hoàn tác toàn bộ đợt (`id_bangke`) của GV: reset `#_xd_hocvien` (ngay_thanh_toan=null, id_bangke=0, quan_ly_duyet=0, so_tien_thanh_toan=0, dinh_muc=0), reset `#_xd_hoadon` (da_quyettoan=0, ngay_thanh_toan=null, quan_ly_duyet=0, id_bangke=0), delete `#_xd_bangke`; báo số đợt hoàn tác (thanh toán thủ công id_bangke=0 không bị đụng)
- [x] 2.3 `admin/sources/xangdau.php`: thêm `case "huyDuyetGiaoVien"` (gọi `xd_huy_duyet_giao_vien()`)
- [x] 2.4 `admin/sources/xangdau/permissions.php`: map `huyDuyetGiaoVien` → `!xd_can_duyet()` trong `xd_act_denied()`
- [x] 2.5 `admin/templates/xangdau/loc/items_dathanhtoan_tpl.php`: nút "Hủy duyệt" theo GV (confirm), chỉ hiện khi `xd_can_duyet()`
- [ ] 2.6 (Tùy chọn) Mở rộng lọc theo tên GV + liệt kê từng đợt riêng để hủy duyệt theo từng đợt thay vì toàn bộ GV

## 3. Export "Tổng hợp GV đã duyệt"

- [x] 3.1 `admin/sources/xangdau/export_tonghop.php`: thêm `xd_xuat_tong_hop_da_duyet()` — truy vấn GV `da_quyettoan=1` theo lọc kỳ + khoảng `ngay_thanh_toan`; cột STT/Giáo viên/SL HV đã TT/Số tiền/Ngày quyết toán + Tổng cộng + chữ ký (tái dùng bố cục hiện có)
- [x] 3.2 `admin/sources/xangdau.php`: thêm `case "xuatTongHopDaDuyet"`
- [x] 3.3 `admin/sources/xangdau/permissions.php`: thêm `xuatTongHopDaDuyet` vào nhóm export (`xd_can_duyet() || xd_can_kiem_tra()`)
- [x] 3.4 `admin/templates/xangdau/loc/items_dathanhtoan_tpl.php`: nút "Xuất tổng hợp đã duyệt" (giữ tham số lọc), hiện cho kế toán và admin

## 3.5 Export "GV đã kiểm toán" (GV đã được kế toán xem xét)

- [x] 3.5.1 `admin/sources/xangdau/export_tonghop.php`: thêm `xd_xuat_da_kiem_tra_giao_vien()` — truy vấn GV `ke_toan_kiem_tra=1 AND da_quyettoan=0` theo lọc tên/khoảng ngày kiểm tra; cột STT/Giáo viên/Số HĐ/Tổng tiền/Ngày kiểm tra + tổng cộng + chữ ký
- [x] 3.5.2 `admin/sources/xangdau.php`: thêm `case "xuatDaKiemTraGiaoVien"`
- [x] 3.5.3 `admin/templates/xangdau/loc/items_dathanhtoan_tpl.php`: thêm nút "Xuất GV đã kiểm toán" ở thanh công cụ (giữ tham số lọc), hiện cho kế toán và admin

## 4. Export Excel HĐ XD & Học viên XD (Tổng / Đã TT / Chưa TT)

- [x] 4.1 `hoadon_crud.php`: tách helper build where/params dùng chung cho `xd_get_hoadon()` và export; thêm `xd_xuat_hoadon_excel()` nhận `scope ∈ {all,paid,unpaid}` (paid=da_quyettoan=1)
- [x] 4.2 `hocvien_crud.php`: tương tự, thêm `xd_xuat_hocvien_excel()` (paid=ngay_thanh_toan is not null)
- [x] 4.3 `admin/sources/xangdau.php`: thêm `case "xuatHoadonExcel"` và `case "xuatHocvienExcel"`
- [x] 4.4 `permissions.php`: thêm 2 act vào nhóm export (`xd_can_duyet() || xd_can_kiem_tra()`)
- [x] 4.5 `admin/templates/xangdau/hoadon/items_tpl.php` + `hocvien/items_tpl.php`: nhóm nút "Xuất Excel" (Tổng/Đã TT/Chưa TT) build URL từ bộ lọc hiện hành

## 5. Sửa trạng thái Hợp lệ + làm rõ xóa/import bổ sung

- [x] 5.1 `hoadon_crud.php`: thêm `xd_toggle_hop_le_hoadon()` — lật `hop_le` 0↔1, chỉ khi `da_quyettoan=0`; báo lỗi nếu đã quyết toán
- [x] 5.2 `admin/sources/xangdau.php`: thêm `case "toggleHopLeHoadon"`
- [x] 5.3 `permissions.php`: map `toggleHopLeHoadon` → `!xd_can_kiem_tra()` (kế toán/admin)
- [x] 5.4 `admin/templates/xangdau/hoadon/items_tpl.php`: nút đổi "Hợp lệ" cạnh badge/hành động, chỉ hiện khi chưa quyết toán và có quyền
- [x] 5.5 Làm rõ gate nút "Xóa" từng dòng theo `xd_can_xoa()` ở `hoadon/items_tpl.php` + `hocvien/items_tpl.php` (giữ hành vi chặn khi đã quyết toán/đã TT)
- [x] 5.6 Ghi chú trên trang upload (`uploadHoadon/items_tpl.php`, `uploadHocvien/items_tpl.php`): import là bổ sung/cập nhật, không xóa dữ liệu cũ

## 6. Kiểm thử & Triển khai

- [x] 6.1 Lint PHP (`php -l`) tất cả file đã sửa
- [ ] 6.2 Test định mức theo khóa: cấu hình CK×c1 khác CK chung → chạy lọc → số HV chọn đổi đúng; khóa lạ → fallback nhóm
- [ ] 6.3 Test duyệt 1 GV rồi hủy duyệt đợt → hóa đơn/học viên về chưa quyết toán, `#_xd_bangke` bị xóa; duyệt lại được
- [ ] 6.4 Test export "Tổng hợp GV đã duyệt" (kế toán và admin) đúng dữ liệu đã quyết toán
- [ ] 6.5 Test export HĐ/HV theo 3 scope khớp bộ lọc đang xem
- [ ] 6.6 Test toggle hợp lệ (chặn khi đã quyết toán), xóa từng dòng, import bổ sung giữ dữ liệu cũ
- [ ] 6.7 Xác minh route/menu/quyền cho các act mới (không 403 nhầm; kế toán vs admin đúng phạm vi)
