## Why

Khi cần import lại danh sách học viên của một giáo viên, màn Học viên XD hiện chỉ có xóa toàn bộ hoặc xóa từng dòng. Việc xóa thủ công nhiều dòng chậm và dễ bỏ sót; xóa toàn bộ lại có nguy cơ làm mất dữ liệu của các giáo viên khác.

## What Changes

- Thêm chức năng xóa toàn bộ học viên theo giáo viên trong màn danh sách Học viên XD.
- Cho phép chọn giáo viên theo tên hiển thị, nhưng thực hiện xóa theo `gv_key` đã chuẩn hóa để tránh phụ thuộc cách viết hoa/khoảng trắng.
- Chỉ xóa học viên chưa thanh toán; học viên đã thanh toán phải bị chặn và được báo rõ số lượng không thể xóa.
- Yêu cầu xác nhận trước khi xóa và hiển thị số bản ghi đã xóa.
- Gắn hành động với quyền xóa dữ liệu xăng dầu (`xd_can_xoa()`), không mở rộng quyền cho kế toán chỉ có quyền kiểm tra/import.
- Sau khi xóa, giáo viên vẫn có thể import lại danh sách học viên mới.

## Capabilities

### Modified Capabilities

- `fuel-cost-admin`: quản lý học viên XD theo giáo viên, gồm lọc/chọn giáo viên, xóa hàng loạt an toàn và import lại dữ liệu.

## Impact

- **Admin sources**: `admin/sources/xangdau/hocvien_crud.php`, `admin/sources/xangdau.php`, `admin/sources/xangdau/permissions.php`.
- **Templates**: `admin/templates/xangdau/hocvien/items_tpl.php` và các template liên quan nếu cần hiển thị bộ lọc/nút xóa.
- **Database**: không thêm bảng hoặc cột; dùng `gv_key`, `ngay_thanh_toan`, `id_bangke` hiện có.
- **Import**: không thay đổi định dạng file; sau khi xóa có thể import lại theo luồng hiện tại.