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

## Checklist Rollout/Backfill Production

- [ ] Backup DB production trước migration (ít nhất `table_product`).
- [ ] Chạy migration idempotent `migration_payroll_employee.sql` trên production.
- [ ] Verify schema sau migration: có đủ cột `ma_tra_cuu` + nhóm `payroll_*` và index `idx_product_type_ma_tra_cuu`.
- [ ] Triển khai code import/lookup mới lên production.
- [ ] Smoke test import 1 file mẫu payroll trên môi trường production-like trước khi import diện rộng.
- [ ] Backfill dữ liệu cũ cho `nhan-vien`:
- [ ] Với record chưa có `ma_tra_cuu`, sinh mã theo rule thống nhất và đảm bảo unique.
- [ ] Nếu còn dữ liệu payroll nằm trong `options2`, map sang cột `payroll_*` bằng script backfill.
- [ ] Verify sau backfill: tổng số nhân viên không đổi, tỷ lệ `ma_tra_cuu` rỗng = 0.
- [ ] Chạy regression script `scripts/regression_import_gplx_gxn_qr.sh` sau deploy để đảm bảo không ảnh hưởng import cũ.
- [ ] Bật giám sát 24h đầu: theo dõi log import/lookup và rollback plan nếu có lỗi nghiêm trọng.
