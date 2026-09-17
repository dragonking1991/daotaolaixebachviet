## 1. Backend

- [x] 1.1 Thêm helper lấy danh sách giáo viên và thống kê số học viên theo `gv_key`.
- [x] 1.2 Thêm `xd_delete_hocvien_by_gv()` trong `hocvien_crud.php`.
- [x] 1.3 Chặn `gv_key` rỗng, bản ghi đã thanh toán và bản ghi thuộc bảng kê đã duyệt.
- [x] 1.4 Thực hiện xóa trong transaction, rollback khi lỗi, trả thông báo số lượng đã xóa/giữ lại.
- [x] 1.5 Thêm route `deleteHocvienByGv` trong `admin/sources/xangdau.php`.

## 2. Permissions

- [x] 2.1 Map `deleteHocvienByGv` vào `xd_can_xoa()` trong `permissions.php`.
- [x] 2.2 Kiểm tra quyền ở server trước khi truy vấn hoặc xóa dữ liệu.

## 3. Admin UI

- [x] 3.1 Thêm bộ lọc chọn giáo viên theo `gv_key`/tên giáo viên trong danh sách Học viên XD.
- [x] 3.2 Hiển thị số học viên có thể xóa và số học viên được giữ lại.
- [x] 3.3 Thêm nút xóa theo giáo viên, chỉ hiện khi có `xd_can_xoa()`.
- [x] 3.4 Thêm confirm nêu rõ tên giáo viên và phạm vi xóa.
- [x] 3.5 Redirect về danh sách sau khi xóa và giữ lại bộ lọc giáo viên.

## 4. Verification

- [x] 4.1 Lint toàn bộ PHP file đã sửa.
- [ ] 4.2 Tạo dữ liệu kiểm thử cho hai giáo viên và trạng thái đã/chưa thanh toán.
- [ ] 4.3 Xác nhận chỉ học viên chưa thanh toán của giáo viên được chọn bị xóa.
- [ ] 4.4 Xác nhận học viên đã thanh toán, bảng kê và giáo viên khác không bị ảnh hưởng.
- [ ] 4.5 Import lại file của giáo viên vừa xóa bằng Playwright và xác nhận dữ liệu xuất hiện đúng.