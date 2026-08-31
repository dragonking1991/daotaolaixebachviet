## Why

Phân hệ chi phí xăng dầu (`add-fuel-cost-management`) đã chạy được với file mẫu, nhưng dữ liệu thật của trường phát sinh 3 vấn đề chặn nghiệp vụ:

1. **File thật đa dạng tiêu đề cột**: file theo dõi học viên thật (`THEO DÕI XĂNG DẦU`) đặt tên cột khác mẫu — giáo viên nằm ở cột **PHÂN XE** (không phải "GIÁO VIÊN"), nhóm nằm ở cột **GHI CHÚ** (giá trị BT/CK/DAT), và có thêm cột **NGƯỜI NỘP**. Bộ nhận diện cột hiện tại bỏ sót nên import không hiển thị đủ thông tin.
2. **File thật là định dạng `.xlsb`**: file học viên được lưu dưới dạng Excel nhị phân `.xlsb` mà PHPExcel không đọc được, nên admin không import trực tiếp được.
3. **Chưa có bảng kê đúng mẫu**: sau khi tổng hợp hóa đơn + danh sách học viên, kế toán cần **"Bảng kê trích chi phí nhiên liệu"** theo đúng mẫu (thông tin trung tâm, giáo viên, ngày quyết toán, bảng hóa đơn chi tiết, bảng danh sách học viên, tổng cộng, chữ ký) và xuất Excel. Bản export hiện tại mới chỉ liệt kê học viên, thiếu bảng hóa đơn và bố cục mẫu.

Ngoài ra cần xác nhận lại luồng công khai: **giáo viên tự tra cứu hóa đơn bằng cách nhập CCCD của giáo viên** (giống tra cứu nhân viên) hoạt động đúng với dữ liệu import theo tên.

## What Changes

- **Nhận diện cột linh hoạt cho cả 2 file** (hóa đơn & học viên): thêm biến thể tiêu đề thật — teacher: `PHÂN XE` | `GIÁO VIÊN`; nhóm: `GHI CHÚ` | `Nhóm`; kèm `NGƯỜI NỘP`, `KHÓA`, `Thông tin bán hàng`, `Chi tiết`, `Biển số xe`. Nếu tiêu đề nhóm không rõ, nhận diện cột nhóm theo giá trị (BT/CK/DAT).
- **Import hiển thị đầy đủ thông tin**: lưu và hiển thị Họ tên, Khóa, Ngày sinh, CCCD, Người nộp, Giáo viên (PHÂN XE), Nhóm cho học viên; và Số HĐ, Ngày, Thông tin bán hàng, Chi tiết, Số tiền, Biển số xe, Giáo viên cho hóa đơn.
- **Hỗ trợ import `.xlsb`**: chấp nhận `.xlsb` (ngoài `.xlsx`/`.xls`); tự chuyển đổi sang `.xlsx` bằng công cụ có sẵn trên máy chủ (LibreOffice `soffice` hoặc bộ chuyển khác) rồi đọc bằng PHPExcel; chọn đúng sheet dữ liệu theo tên (ví dụ `học viên`). Nếu máy chủ không có công cụ chuyển đổi, báo lỗi rõ ràng và hướng dẫn lưu lại `.xlsx`.
- **Xuất "Bảng kê trích chi phí nhiên liệu" đúng mẫu**: xuất Excel theo template ảnh — tiêu đề trung tâm, "Giáo viên", "Ngày quyết toán", bảng **Nội dung** (STT, Số hóa đơn, Ngày, Thông tin bán hàng, Chi tiết, Số tiền HĐ, Biển số xe + Tổng cộng), bảng **Danh sách học viên** (STT, Khóa, Họ tên, CCCD/CC, Năm sinh, Định mức, Số tiền thanh toán, Nhóm + Tổng cộng), và các dòng chữ ký (Phòng Đào tạo, Kế Toán, Giáo viên quyết toán). Xuất theo từng giáo viên đã được lọc.
- **Xác nhận cổng GV**: giáo viên nhập CCCD (như tra cứu nhân viên) để xem hóa đơn và học viên đã thanh toán của mình; đảm bảo khớp với dữ liệu import theo tên giáo viên.

## Capabilities

### Modified Capabilities
- `fuel-cost-admin`: mở rộng import (nhận diện cột linh hoạt cho tiêu đề thật, hỗ trợ `.xlsb`, lưu đủ chi tiết hóa đơn) và thay thế bản xuất bảng kê bằng "Bảng kê trích chi phí nhiên liệu" đúng mẫu (kèm bảng hóa đơn) theo từng giáo viên.
- `fuel-cost-teacher-portal`: xác nhận và hoàn thiện tra cứu công khai của giáo viên bằng CCCD (bắc cầu CCCD → hồ sơ nhân viên → tên → dữ liệu XD theo tên).

## Impact

- **Database**: thêm cột chi tiết hóa đơn cho bảng kê (`thong_tin_ban_hang`, `chi_tiet`) vào `table_xd_hoadon` (idempotent); `bien_so`, `khoa`, `nguoi_nop`, `gv_key` đã có từ change trước.
- **Admin**: cập nhật `admin/sources/xangdau.php` (nhận diện cột, đọc `.xlsb`, hàm xuất bảng kê theo mẫu); template hiển thị thêm cột; nút "Xuất bảng kê" theo từng GV.
- **Server/Deploy**: cần công cụ chuyển đổi `.xlsb` → `.xlsx` trên máy chủ (LibreOffice headless `soffice`) cho luồng xlsb; nếu thiếu thì degrade sang thông báo lỗi. Ghi chú trong `Dockerfile`/hướng dẫn triển khai.
- **Frontend**: không đổi cấu trúc; đảm bảo cổng GV (`ajax/tracuu_xangdau.php`) khớp dữ liệu.
- **Dependencies**: không thêm thư viện PHP; tái dùng `libraries/PHPExcel`. Phụ thuộc tùy chọn công cụ hệ thống (`soffice`) cho `.xlsb`.
