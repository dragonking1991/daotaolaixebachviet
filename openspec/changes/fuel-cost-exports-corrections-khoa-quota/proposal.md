## Why

Phân hệ chi phí xăng dầu (XD) đã có luồng import → kiểm tra (kế toán) → duyệt (quản lý) → xuất bảng kê. Vận hành thực tế phát sinh thêm 5 nhu cầu nghiệp vụ chưa được đáp ứng:

1. **Xuất danh sách GV đã duyệt (cả kế toán và admin)**: sau khi kiểm tra và duyệt, cả kế toán lẫn admin cần xuất Excel *tổng hợp thống kê các giáo viên đã duyệt* (đã quyết toán). Bản xuất "tổng hợp" hiện tại (`xd_xuat_tong_hop_giao_vien`) chạy trên kết quả **thuật toán dự kiến** (chưa duyệt), không phải danh sách GV đã duyệt thực tế.
2. **Hủy duyệt theo đợt**: khi một GV đã kiểm tra và duyệt nhầm, admin cần **lọc lại theo tên GV** và **hủy duyệt theo đợt (bảng kê)** để đưa hóa đơn/học viên về trạng thái chưa quyết toán. Hiện chỉ có "hủy kiểm tra" cho GV chưa quyết toán; không có đường quay lui sau khi đã duyệt.
3. **Xuất Excel trong HĐ XD và Học viên XD**: hai trang danh sách Hóa đơn XD và Học viên XD cần nút xuất Excel theo bộ lọc hiện hành với 3 phạm vi: **Tổng danh sách**, **Đã thanh toán**, **Chưa thanh toán**.
4. **Sửa/xóa thủ công từng bản ghi**: admin cần **đổi trạng thái "Hợp lệ"** của từng hóa đơn, **import bổ sung** hoặc **xóa thủ công từng** hóa đơn/học viên khi có sai sót — tránh phải "Xóa toàn bộ" rồi import lại từ đầu.
5. **Định mức XD theo hạng khóa cho nhóm CK/DAT**: nhóm CK và DAT cần định mức XD (số chia để tính số học viên được trích) **theo từng hạng khóa** trong cột "Khóa" của file import: `bss`, `btd`, `c1`, `c`, `ce`. Hiện định mức XD chỉ cấu hình một mức chung cho mỗi nhóm (BT/CK/DAT).

## What Changes

- **Xuất "Tổng hợp GV đã duyệt"**: thêm bản xuất Excel liệt kê các giáo viên **đã duyệt** (theo `#_xd_bangke` / hóa đơn `da_quyettoan = 1`) trong khoảng ngày/kỳ, gồm STT, Giáo viên, SL học viên đã thanh toán, Số tiền, ngày quyết toán. Nút hiển thị cho **cả kế toán và admin** ở màn "Đã thanh toán".
- **Hủy duyệt theo đợt**: bổ sung màn lọc GV đã duyệt theo tên + hành động **hủy duyệt cả đợt (bảng kê)**: đưa hóa đơn về `da_quyettoan = 0`, gỡ `ngay_thanh_toan`/`id_bangke`/`quan_ly_duyet` của học viên trong đợt, và xóa/đánh dấu bản ghi `#_xd_bangke` tương ứng. Chỉ **admin (quyền duyệt)** được thao tác; ghi log người thực hiện.
- **Nút xuất Excel ở HĐ XD & Học viên XD**: mỗi trang thêm nhóm nút "Xuất Excel" với 3 lựa chọn — Tổng danh sách / Đã thanh toán / Chưa thanh toán — áp dụng đúng bộ lọc đang xem (từ khóa, ngày, kỳ, nhóm).
- **Sửa trạng thái Hợp lệ + xóa/sửa thủ công từng bản ghi**: thêm hành động đổi `hop_le` (0/1) cho từng hóa đơn (chỉ khi chưa quyết toán); xác nhận việc **xóa từng** hóa đơn/học viên (đã có, giữ và làm rõ quyền admin) và **import bổ sung** (append) không xóa dữ liệu cũ.
- **Định mức XD theo hạng khóa cho CK/DAT**: mở rộng cấu hình để nhập định mức XD riêng cho tổ hợp **nhóm (CK/DAT) × hạng khóa (bss/btd/c1/c/ce)**; thuật toán lọc dùng định mức theo (nhóm, hạng khóa) khi tính số học viên được trích, fallback về định mức chung của nhóm khi hạng khóa không khớp/không cấu hình.

## Capabilities

### Modified Capabilities
- `fuel-cost-admin`: (a) thêm export "Tổng hợp GV đã duyệt" cho cả kế toán và admin; (b) thêm luồng hủy duyệt theo đợt (lọc theo tên GV); (c) thêm export Excel Tổng/Đã TT/Chưa TT ở danh sách Hóa đơn và Học viên; (d) thêm sửa trạng thái Hợp lệ và làm rõ sửa/xóa/import bổ sung thủ công từng bản ghi; (e) mở rộng cấu hình + thuật toán định mức XD theo hạng khóa cho nhóm CK/DAT.

## Impact

- **Database**: không thêm bảng mới. Định mức theo hạng khóa lưu trong `#_xd_config` bằng các khóa cấu hình mới (ví dụ `xd_dinh_muc_ck_bss`, `xd_dinh_muc_dat_c1`, ...); tương thích ngược (thiếu → fallback định mức nhóm). Không đổi cột dữ liệu; tận dụng `da_quyettoan`, `id_bangke`, `ngay_thanh_toan`, `quan_ly_duyet`, `hop_le` sẵn có.
- **Admin (`admin/sources/xangdau/*`)**:
  - `export_tonghop.php`: thêm hàm xuất "Tổng hợp GV đã duyệt".
  - `loc.php`: thêm lọc GV đã duyệt theo tên + hàm hủy duyệt theo đợt.
  - `hoadon_crud.php` / `hocvien_crud.php`: thêm export Tổng/Đã TT/Chưa TT theo bộ lọc; thêm đổi `hop_le`.
  - `xangdau.php`: thêm các `act` mới; `permissions.php`: gắn quyền (export = kế toán/admin; hủy duyệt & đổi hợp lệ = quyền duyệt/quản lý).
  - `config.php` + `libraries/xangdau_config.php`: đọc/ghi + helper định mức theo (nhóm, hạng khóa).
  - `algorithm.php`: dùng định mức theo hạng khóa cho CK/DAT.
- **Templates (`admin/templates/xangdau/*`)**: nút export ở `hoadon/items`, `hocvien/items`, `loc/items_dathanhtoan`; nút đổi hợp lệ ở `hoadon/items`; form định mức theo hạng khóa ở `config/item_edit`.
- **Frontend/Cổng GV**: không đổi.
- **Dependencies**: không thêm thư viện; tái dùng `libraries/PHPExcel`.
