# Proposal: Payroll Employee Import V2

## Why

Import `nhan-vien` hiện không hoạt động đúng với file lương thực tế vì header thật nằm ở dòng 12, không phải dòng 1. Ngoài ra dữ liệu payroll đang được lưu chủ yếu trong `options2`, chưa có cột DB riêng để tra cứu/lọc/báo cáo ổn định.

Nhu cầu mới:

1. Nhận diện header theo hướng linh hoạt (Option B) nhưng phù hợp thực tế hiện tại là header ở dòng 12.
2. Cho phép import khi không có CCCD, và có field tham chiếu riêng để tra cứu/cập nhật sau.
3. Lưu toàn bộ cột payroll thành field riêng trong DB.
4. Dòng aggregate (ví dụ bộ phận/phòng ban) được giữ làm ngữ cảnh và gán cho nhân viên bên dưới.
5. Public tra cứu chỉ dùng `ma_tra_cuu`.
6. Khi admin cập nhật `cccd` cho nhân viên, hệ thống tự đồng bộ `ma_tra_cuu = cccd`.
7. Khi import có cột `cccd`, hệ thống ưu tiên lấy `cccd` làm `ma_tra_cuu`.

## Proposed Change

### 1) Header Detection (Option B)

- Quét trong phạm vi dòng 1..20 để tìm dòng header có số lượng match alias cao nhất.
- Với file hiện tại, dòng 12 sẽ được chọn tự động.
- Dữ liệu bắt đầu từ `header_row + 1`.

### 2) Employee Identity Khi Chưa Có CCCD

- CCCD không còn là điều kiện bắt buộc để import.
- Thêm field tham chiếu mới trong DB (ví dụ `ma_tra_cuu`) để dùng cho tra cứu tạm thời trước khi có CCCD.
- Field này cho phép chỉnh sửa trong admin/public flow theo rule nghiệp vụ.
- Public endpoint chỉ tra cứu theo `ma_tra_cuu`.
- Nếu `cccd` có dữ liệu (khi import hoặc khi admin chỉnh sửa), đồng bộ `ma_tra_cuu` theo `cccd`.

### 3) Store Payroll Columns In Dedicated DB Fields

Tách các cột sau thành field riêng trong DB (không chỉ JSON):

- Họ và tên
- Chức vụ
- Số ngày làm việc
- Lương chính
- Thưởng lễ tết
- Tiền cơm
- Phụ cấp xăng xe
- Dạy LT Sát hạch
- Chiêu sinh TTTN
- Khác (DT - Khác)
- Làm thêm giờ
- Điện thoại
- Tổng thu nhập
- NLD Nộp BHXH 10.5%
- TT Nộp BHXH 21.5%
- Thu nhập chịu thuế
- Giảm trừ gia cảnh
- Số NPT
- Người phụ thuộc
- Thu nhập tính thuế
- Bậc
- Thuế TNCN
- Lương thực nhận
- Nghĩa vụ GV

Vẫn lưu `options2` như bản ghi nguồn để trace/debug.

### 4) Aggregate Department Row Handling

- Nhận diện dòng aggregate là dòng bộ phận/phòng ban (không phải nhân viên).
- Cập nhật `current_department` từ dòng này.
- Gán `current_department` cho các nhân viên kế tiếp tới khi gặp bộ phận mới.
- Có field DB riêng để lưu phòng ban payroll phục vụ tra cứu.

### 5) Sheet Selection — Chỉ Đọc Sheet "L"

- Khi import file `.xlsm` / `.xlsx`, chỉ xử lý sheet có tên là **`L`**, bỏ qua tất cả các sheet khác (`BK TT`, `Bảng kê GV`, `Bảng kê VP`, `Bảng kê đóng BHXH`, `Sheet4`…).
- Nếu file không có sheet `L`, báo lỗi rõ ràng thay vì import sai sheet.

### 6) Phân Loại Bộ Phận Trong Sheet L

- Dòng aggregate chứa text **`Bộ phận giáo viên`** (không phân biệt hoa thường, có thể có khoảng trắng thừa) là mốc phân chia:
  - Các nhân viên **phía trên** dòng này thuộc bộ phận **`van_phong`**.
  - Các nhân viên **phía dưới** dòng này thuộc bộ phận **`giao_vien`**.
- Giá trị `payroll_department` được lưu vào DB theo hai nhóm trên.

### 7) Cột Bổ Sung Cho Giáo Viên (TĐ, SS, C1, CE)

- Với nhân viên thuộc bộ phận `giao_vien`, đọc thêm bốn cột sau từ sheet L và lưu vào DB:

| Tên cột Excel | Field DB          | Kiểu  | Ý nghĩa                            |
|---------------|-------------------|-------|------------------------------------|
| TĐ            | `payroll_td`      | INT   | Số học viên điều hành loại TĐ      |
| SS            | `payroll_ss`      | INT   | Số học viên điều hành loại SS      |
| C1            | `payroll_c1`      | INT   | Số học viên điều hành loại C1      |
| CE            | `payroll_ce`      | INT   | Số học viên điều hành loại CE      |

- Với nhân viên văn phòng, các field này để NULL.

### 8) Config Đơn Giá Học Viên (Per-Type Rate)

Lưu config trong một bảng/file riêng để admin có thể chỉnh sửa sau mà không cần sửa code:

| Loại | Đơn giá mặc định |
|------|-----------------|
| TĐ   | 1.000.000 VNĐ   |
| SS   | 2.000.000 VNĐ   |
| C1   | 2.000.000 VNĐ   |
| CE   | 1.100.000 VNĐ   |

Config key đề xuất: `payroll_rate_td`, `payroll_rate_ss`, `payroll_rate_c1`, `payroll_rate_ce`.

### 9) Public Tra Cứu — Hiển Thị Theo Bộ Phận

#### Giáo viên

Màn tra cứu public chỉ hiển thị các mục sau (theo thứ tự):

| Mục                            | Nguồn dữ liệu                                                   |
|--------------------------------|-----------------------------------------------------------------|
| Lương thực nhận                | `payroll_luong_thuc_nhan`                                       |
| Khoản phải nộp                 | `payroll_nghia_vu_gv`                                           |
| **Nhận**                       | `payroll_luong_thuc_nhan` − `payroll_nghia_vu_gv`               |
| Lương CE                       | `payroll_ce` × config `payroll_rate_ce`                         |
| L theo danh sách phân xe       | `payroll_td`×`rate_td` + `payroll_ss`×`rate_ss` + `payroll_c1`×`rate_c1` |
| B(TĐ) — K116                   | `payroll_td`                                                    |
| B(SS) — K195                   | `payroll_ss`                                                    |
| C1 — K11                       | `payroll_c1`                                                    |
| Thưởng lễ                      | `payroll_thuong_le_tet`                                         |
| Thanh toán TN + CP chiêu sinh  | `payroll_chieu_sinh_tttn`                                       |
| Phụ cấp thêm                   | `payroll_phu_cap_xang_xe`                                       |
| BHXH                           | `payroll_nld_nop_bhxh`                                          |
| Thuế TNCN (nếu có)             | `payroll_thue_tncn`                                             |
| **Nhận** (kiểm tra lại)        | tổng kiểm tra cuối phiếu                                        |
| Người phụ thuộc                | `payroll_nguoi_phu_thuoc`                                       |

#### Văn phòng

Màn tra cứu public hiển thị phiếu lương theo dạng nhân viên văn phòng (layout như hiện tại với các mục: TT chuyển, L căn bản, Phụ cấp TN, Phụ cấp chuyên cần + KPI, Thanh toán TN + CP chiêu sinh, Phụ cấp cơm + xăng, Phụ cấp điện thoại, L làm thêm giờ, Dạy LT + SH, Thưởng lễ, BHXH 10.5%, Thuế TNCN, Nhận, Người phụ thuộc).

## Scope

### In Scope

1. Thêm migration DB cho các field mới (tham chiếu tra cứu + payroll + phòng ban payroll + `payroll_td/ss/c1/ce`).
2. Thêm bảng/cấu hình `payroll_config` để lưu đơn giá từng loại học viên (TĐ, SS, C1, CE).
3. Cập nhật import `nhan-vien` trong `admin/sources/import.php`:
   - Chỉ đọc sheet có tên `L`.
   - Phân tách bộ phận theo dòng `Bộ phận giáo viên`.
   - Đọc cột TĐ, SS, C1, CE cho nhân viên giáo viên.
4. Cập nhật rule insert/update để không phụ thuộc CCCD khi import từ payroll.
5. Đồng bộ dữ liệu tối thiểu với các field cũ đang dùng trong giao diện hiện tại (`tenvi`, `khoa`, `hang`, `cccd`).
6. Public tra cứu hiển thị phiếu lương theo bộ phận (giáo viên / văn phòng).

### Out of Scope

1. Không triển khai tính lương tự động ngoài các công thức tra cứu public đã mô tả.
2. Không làm versioning nhiều kỳ lương trong cùng change này.
3. Không refactor toàn bộ màn admin ngoài phần cần để hiển thị/chỉnh sửa field mới.

## Success Criteria

1. Import file payroll mẫu thành công, không còn trường hợp báo success nhưng insert 0 dòng do sai header row.
2. Mỗi nhân viên được lưu đủ dữ liệu payroll trong cột DB riêng.
3. Nhân viên chưa có CCCD vẫn được import và tra cứu qua field tham chiếu mới.
4. Dòng aggregate được map thành thông tin phòng ban cho đúng nhóm nhân viên.
5. Chỉ sheet `L` được xử lý; import file có nhiều sheet không bị lệch dữ liệu.
6. Nhân viên thuộc đúng bộ phận (`giao_vien` / `van_phong`) dựa trên vị trí tương đối so với dòng `Bộ phận giáo viên`.
7. Giáo viên có đủ 4 field học viên `payroll_td/ss/c1/ce`; văn phòng để NULL.
8. Admin có thể xem và chỉnh sửa đơn giá học viên qua config mà không cần deploy lại.
9. Public tra cứu giáo viên hiển thị phiếu lương đúng công thức; tra cứu văn phòng hiển thị layout văn phòng.
