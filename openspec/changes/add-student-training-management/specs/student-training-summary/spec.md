## ADDED Requirements

### Requirement: Themed collapsible training navigation

Training navigation SHALL retain all existing child URLs and permissions, show Overview separately, and organize the remaining items into five collapsible groups: Hồ sơ & nguồn lực; Quá trình học; Kiểm tra & hoàn tất; Tài chính; Quản trị. Group styling SHALL be scoped to training navigation on every admin page, with readable wrapping labels and keyboard focus.

#### Scenario: Open a training page or subpage
- **WHEN** a user opens a primary training page, import/detail page or entity CRUD form
- **THEN** the corresponding permitted child link SHALL be highlighted and its group expanded automatically, while unrelated groups remain initially collapsed. Users SHALL be able to expand and collapse groups using the existing AdminLTE treeview.

#### Scenario: Restricted menu visibility
- **WHEN** a user has access only to some training sections
- **THEN** only the existing permitted links SHALL be rendered and empty groups SHALL be omitted; theory permissions SHALL retain access to DAT entry eligibility and cabin-only permissions SHALL NOT expose other sections.
- **AND** overview and administration tools SHALL remain restricted to training administrators.

### Requirement: Tổng hợp Đạt/Không đạt 4 module

Hệ thống SHALL tổng hợp kết quả 4 module (Lý thuyết, Cabin, Thực hành trong hình, DAT) của mỗi học viên và kết luận đủ/chưa đủ điều kiện kiểm tra.

#### Scenario: Học viên đủ điều kiện
- **WHEN** một học viên đạt cả 4 module
- **THEN** hệ thống SHALL kết luận học viên đủ điều kiện kiểm tra

#### Scenario: Học viên chưa đủ điều kiện
- **WHEN** một học viên chưa đạt ít nhất một module
- **THEN** hệ thống SHALL kết luận chưa đủ điều kiện và nêu rõ module còn thiếu

### Requirement: Tra cứu tổng hợp đa tiêu chí

Hệ thống SHALL cho phép admin tra cứu tổng hợp theo khoảng ngày (từ ngày – tới ngày), CCCD, họ tên học viên, khóa, hạng, giáo viên.

#### Scenario: Lọc theo nhiều tiêu chí
- **WHEN** admin chọn khoảng ngày và/hoặc CCCD/họ tên/khóa/hạng/giáo viên
- **THEN** hệ thống SHALL hiển thị danh sách học viên khớp cùng trạng thái từng module và kết luận tổng

### Requirement: Xuất Excel danh sách Đạt/Không đạt

Hệ thống SHALL cho phép admin xuất Excel danh sách học viên Đạt và Không đạt kèm chi tiết 4 module.

#### Scenario: Xuất Excel tổng hợp
- **WHEN** admin chọn xuất Excel theo bộ lọc hiện tại
- **THEN** hệ thống SHALL tạo file `.xlsx` gồm mã học viên, tên, hạng, khóa, trạng thái từng module và chi tiết tham số (tổng giờ H, tổng quãng đường H, tổng giờ Đ, tổng quãng đường Đ, A, B, C, D)

### Requirement: Thống kê đạt/chưa đạt theo module

Trang tổng hợp SHALL hiển thị số học viên đạt/chưa đạt cho Lý thuyết, Cabin, Thực hành trong hình và DAT theo danh sách lọc hiện tại. Kết quả mô phỏng SHALL được tính một lần trong Lý thuyết; học viên ở khóa không yêu cầu Cabin SHALL không bị tính là chưa đạt Cabin.

#### Scenario: Thống kê trên danh sách tổng hợp đã lọc
- **WHEN** admin lọc danh sách tổng hợp theo khóa, học viên, hạng, giáo viên hoặc ngày DAT
- **THEN** hệ thống SHALL cập nhật số đạt/chưa đạt của từng module theo đúng danh sách kết quả lọc
