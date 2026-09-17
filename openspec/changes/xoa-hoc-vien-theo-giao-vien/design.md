## Context

Học viên XD được liên kết với giáo viên bằng `gv_key`, là tên giáo viên đã chuẩn hóa. Danh sách hiện được truy vấn trong `hocvien_crud.php`; quyền xóa hàng loạt được kiểm soát bởi `xd_can_xoa()` và hành động được route qua `admin/sources/xangdau.php`.

## Goals / Non-Goals

**Goals:**
- Xóa nhanh các học viên chưa thanh toán của đúng một giáo viên.
- Bảo vệ học viên đã thanh toán và các giáo viên khác.
- Giữ transaction và thông báo kết quả rõ ràng.
- Cho phép import lại ngay sau khi xóa.

**Non-Goals:**
- Không xóa hóa đơn của giáo viên.
- Không xóa học viên đã thanh toán hoặc bản ghi thuộc bảng kê đã duyệt.
- Không thêm quyền mới nếu `xd_can_xoa()` đã đáp ứng đúng phạm vi.
- Không thay đổi logic nhận diện giáo viên trong import.

## Decisions

### 1. Chọn giáo viên

Thêm bộ lọc `gv_key` hoặc danh sách giáo viên lấy từ các học viên hiện có. UI hiển thị `gv_hoten`, nhưng gửi `gv_key` làm giá trị thao tác. Nút xóa chỉ được bật khi đã chọn giáo viên.

### 2. Phạm vi xóa

Server nhận `gv_key`, sau đó thực hiện:

```sql
delete from #_xd_hocvien
where gv_key = ?
  and ngay_thanh_toan is null
  and id_bangke = 0
```

Trước khi xóa, truy vấn riêng số học viên chưa thanh toán có thể xóa và số học viên còn vướng trạng thái đã thanh toán/bảng kê. Không dùng tên giáo viên trực tiếp trong SQL.

### 3. Bảo vệ dữ liệu đã thanh toán

Nếu còn học viên đã thanh toán của giáo viên, không xóa các bản ghi đó. Sau transaction, thông báo tách bạch: đã xóa bao nhiêu, còn giữ lại bao nhiêu vì đã thanh toán. Nếu có lỗi DB thì rollback toàn bộ thao tác.

### 4. Quyền và route

Thêm act riêng, ví dụ `deleteHocvienByGv`. `xd_act_denied()` map act này tới `!xd_can_xoa()`. Handler kiểm tra quyền ở route và kiểm tra lại điều kiện bản ghi ở server; không tin vào việc ẩn nút trên UI.

### 5. Xác nhận và import lại

Nút hiển thị tên giáo viên và số bản ghi dự kiến xóa trong hộp xác nhận. Sau khi thành công, redirect về danh sách với bộ lọc giáo viên và thông báo kết quả. Luồng upload hiện tại được giữ nguyên để người dùng import lại file.

## Risks / Trade-offs

- `gv_key` có thể rỗng ở dữ liệu cũ: không cho phép xóa theo khóa rỗng để tránh xóa hàng loạt ngoài ý muốn.
- Hai giáo viên có tên chuẩn hóa giống nhau sẽ dùng chung `gv_key`; đây là định danh hiện tại của hệ thống và cần hiển thị cảnh báo số bản ghi trước khi xác nhận.
- Dữ liệu đã xóa không khôi phục được qua UI; phải yêu cầu xác nhận rõ ràng và khuyến nghị backup/import lại từ file nguồn.

## Migration Plan

1. Không cần migration DB.
2. Deploy handler, route, permission mapping và UI.
3. Kiểm tra bằng một giáo viên có học viên chưa thanh toán và một học viên đã thanh toán.
4. Import lại file của giáo viên và xác nhận không ảnh hưởng dữ liệu giáo viên khác.

## Open Questions

- Có cần cho phép xóa cả học viên đã thanh toán sau khi hủy duyệt hay bắt buộc dùng luồng hủy duyệt trước? Mặc định thiết kế là bắt buộc giữ lại, không xóa trực tiếp.