# Tasks

- [x] Xác nhận tên change, tên field tham chiếu mới (`ma_tra_cuu` hoặc tên khác) và quy tắc unique.
- [x] Thiết kế migration: thêm field tham chiếu, field phòng ban payroll, và nhóm field payroll tách cột trong `table_product`.
- [x] Xác định kiểu dữ liệu cho từng field mới (numeric/text) và quy tắc chuẩn hóa số tiền/phần trăm.
- [x] Cập nhật import `nhan-vien` để detect header theo Option B (scan dòng 1..20, chọn dòng match tốt nhất).
- [x] Cập nhật parser dữ liệu: tách rõ dòng header, dòng aggregate phòng ban, dòng nhân viên hợp lệ.
- [x] Cập nhật mapping import từ header tiếng Việt sang field DB mới cho toàn bộ cột payroll yêu cầu.
- [x] Cập nhật logic identity/upsert khi CCCD rỗng: dùng field tham chiếu mới làm khóa tạm.
- [x] Public tra cứu nhân viên chỉ theo `ma_tra_cuu` (không fallback CCCD).
- [x] Khi admin thay đổi `cccd` của `nhan-vien`, tự đồng bộ `ma_tra_cuu = cccd`.
- [x] Khi import có cột `cccd`, ưu tiên lấy `cccd` làm `ma_tra_cuu`.
- [x] Giữ `options2` như snapshot nguồn để đối soát, đồng thời lưu đầy đủ vào cột riêng.
- [x] Cập nhật admin form/list cho `nhan-vien` để hiển thị/chỉnh sửa field tham chiếu mới và các field trọng yếu.
- [x] Viết/điều chỉnh script test import với file mẫu (`Book1.xlsx` và `LUONG.xlsm`) để xác nhận số dòng insert/update đúng kỳ vọng.
- [x] Kiểm tra regression với import các type khác (`gplx`, `gxn`, `qr`) để đảm bảo không ảnh hưởng.
- [x] Chốt tiêu chí nghiệm thu và chuẩn bị rollout/backfill dữ liệu cũ nếu cần.

## Nhóm Task Mới — Sheet L, Phân Loại Bộ Phận & Giáo Viên

- [x] **[Import]** Cập nhật import `nhan-vien` để chỉ đọc sheet có tên `L`; nếu không tìm thấy sheet `L` thì trả lỗi rõ ràng (không fallback sang sheet đầu tiên).
- [x] **[Import]** Nhận diện dòng aggregate chứa text `Bộ phận giáo viên` (case-insensitive, trim whitespace) làm mốc phân chia: trên → `van_phong`, dưới → `giao_vien`; lưu vào `payroll_department`.
- [x] **[Migration]** Thêm migration idempotent để bổ sung 4 cột mới vào `table_product`: `payroll_td INT`, `payroll_ss INT`, `payroll_c1 INT`, `payroll_ce INT` (nullable).
- [x] **[Import]** Đọc cột `TĐ`, `SS`, `C1`, `CE` từ sheet L và lưu vào `payroll_td/ss/c1/ce` cho nhân viên thuộc `giao_vien`; để NULL với nhân viên văn phòng.
- [x] **[Config]** Tạo bảng/cấu hình `payroll_rate_config` (hoặc dùng bảng config chung) với 4 key: `payroll_rate_td=1000000`, `payroll_rate_ss=2000000`, `payroll_rate_c1=2000000`, `payroll_rate_ce=1100000`; admin có thể cập nhật qua giao diện mà không cần deploy lại.
- [x] **[Public — GV]** Cập nhật màn tra cứu public cho nhân viên `giao_vien`: hiển thị đúng danh sách mục theo spec (Lương thực nhận, Khoản phải nộp, Nhận, Lương CE, L theo DS phân xe, B(TĐ), B(SS), C1, Thưởng lễ, Thanh toán TN+CP chiêu sinh, Phụ cấp thêm, BHXH, Thuế TNCN, Nhận, Người phụ thuộc).
- [x] **[Public — GV]** Áp dụng đúng công thức tính cho từng mục GV:
  - `Nhận = payroll_luong_thuc_nhan − payroll_nghia_vu_gv`
  - `Lương CE = payroll_ce × payroll_rate_ce`
  - `L theo DS phân xe = payroll_td×rate_td + payroll_ss×rate_ss + payroll_c1×rate_c1`
  - `B(TĐ) = payroll_td`, `B(SS) = payroll_ss`, `C1 = payroll_c1`
  - `Phụ cấp thêm = payroll_phu_cap_xang_xe`
  - `BHXH = payroll_nld_nop_bhxh`
  - `Người phụ thuộc = payroll_nguoi_phu_thuoc`
- [x] **[Public — VP]** Đảm bảo màn tra cứu public cho nhân viên `van_phong` hiển thị layout phiếu lương văn phòng (TT chuyển, L căn bản, Phụ cấp TN, Phụ cấp chuyên cần+KPI, Thanh toán TN+CP chiêu sinh, Phụ cấp cơm+xăng, Phụ cấp điện thoại, L làm thêm giờ, Dạy LT+SH, Thưởng lễ, BHXH 10.5%, Thuế TNCN, Nhận, Người phụ thuộc).
- [x] **[Test]** Bổ sung test case import file `LUONG MAU.xlsm`: xác nhận số dòng giáo viên (dưới `Bộ phận giáo viên`) và văn phòng (trên) được phân loại đúng.
- [x] **[Test]** Kiểm tra công thức public GV với 2–3 nhân viên mẫu: so sánh kết quả tính với giá trị kỳ vọng từ file Excel gốc.

## Checklist Rollout/Backfill Production

- [ ] Backup DB production trước migration (ít nhất `table_product`).
- [ ] Chạy migration idempotent `migration_payroll_employee.sql` trên production.
- [ ] Verify schema sau migration: có đủ cột `ma_tra_cuu` + nhóm `payroll_*` (bao gồm `payroll_td/ss/c1/ce`) và index `idx_product_type_ma_tra_cuu`.
- [ ] Triển khai code import/lookup mới lên production.
- [ ] Smoke test import 1 file mẫu payroll trên môi trường production-like trước khi import diện rộng.
- [ ] Backfill dữ liệu cũ cho `nhan-vien`:
- [ ] Với record chưa có `ma_tra_cuu`, sinh mã theo rule thống nhất và đảm bảo unique.
- [ ] Nếu còn dữ liệu payroll nằm trong `options2`, map sang cột `payroll_*` bằng script backfill.
- [ ] Verify sau backfill: tổng số nhân viên không đổi, tỷ lệ `ma_tra_cuu` rỗng = 0.
- [ ] Verify `payroll_department` được gán đúng cho toàn bộ nhân viên đã import.
- [ ] Chạy regression script `scripts/regression_import_gplx_gxn_qr.sh` sau deploy để đảm bảo không ảnh hưởng import cũ.
- [ ] Bật giám sát 24h đầu: theo dõi log import/lookup và rollback plan nếu có lỗi nghiêm trọng.
