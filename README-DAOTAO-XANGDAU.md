# HƯỚNG DẪN SỬ DỤNG — ĐÀO TẠO & XĂNG DẦU

Tài liệu dành cho người dùng trang quản trị (admin), kế toán và giáo viên.

## Mục lục

- [1. Tổng quan](#1-tổng-quan)
- [2. Phân quyền](#2-phân-quyền)
- [3. QL Đào tạo](#3-ql-đào-tạo)
  - [3.1 Quy trình chuẩn](#31-quy-trình-chuẩn)
  - [3.2 Khóa đào tạo](#32-khóa-đào-tạo)
  - [3.3 Học viên](#33-học-viên)
  - [3.4 Giáo viên & Xe tập lái](#34-giáo-viên--xe-tập-lái)
  - [3.5 Lý thuyết · Cabin · Thực hành hình · DAT](#35-lý-thuyết--cabin--thực-hành-hình--dat)
  - [3.6 Tổng hợp](#36-tổng-hợp)
  - [3.7 Ngày kiểm tra & Kết quả đào tạo](#37-ngày-kiểm-tra--kết-quả-đào-tạo)
  - [3.8 Số giấy HTKH (giấy xác nhận hoàn thành)](#38-số-giấy-htkh-giấy-xác-nhận-hoàn-thành)
  - [3.9 Kết quả sát hạch](#39-kết-quả-sát-hạch)
  - [3.10 Cổng giáo viên](#310-cổng-giáo-viên)
  - [3.11 Tổng quan · Nhật ký · Sao lưu](#311-tổng-quan--nhật-ký--sao-lưu)
- [4. Xăng dầu (XD)](#4-xăng-dầu-xd)
  - [4.1 Quy trình chuẩn](#41-quy-trình-chuẩn)
  - [4.2 Cấu hình định mức](#42-cấu-hình-định-mức)
  - [4.3 Hóa đơn XD](#43-hóa-đơn-xd)
  - [4.4 Học viên XD](#44-học-viên-xd)
  - [4.5 Lọc thanh toán (kiểm tra → duyệt → bảng kê)](#45-lọc-thanh-toán-kiểm-tra--duyệt--bảng-kê)
  - [4.6 Danh sách CE](#46-danh-sách-ce)
- [5. Đồng bộ Đào tạo (DAT) → Xăng dầu](#5-đồng-bộ-đào-tạo-dat--xăng-dầu)
- [6. Thao tác chung](#6-thao-tác-chung)
- [7. Câu hỏi thường gặp](#7-câu-hỏi-thường-gặp)

---

## 1. Tổng quan

```mermaid
flowchart LR
    subgraph DT[QL Đào tạo]
        K[Khóa] --> HV[Học viên]
        GV[Giáo viên] --> HV
        HV --> LT[Lý thuyết + Mô phỏng]
        HV --> CB[Cabin]
        HV --> H[Thực hành hình]
        HV --> DAT[DAT trên đường]
        LT & CB & H & DAT --> TH[Tổng hợp<br/>Đủ / chưa đủ ĐK]
        TH --> KT[Ngày kiểm tra]
        KT --> KQ[Kết quả đào tạo]
        KQ --> HTKH[Giấy HTKH]
        KQ --> SH[Kết quả sát hạch]
    end
    subgraph XD[Xăng dầu]
        HD[Hóa đơn XD] --> LOC[Lọc thanh toán]
        HVXD[Học viên XD] --> LOC
        LOC --> BK[Bảng kê / Quyết toán]
        CE[Danh sách CE]
    end
    DAT -- đạt DAT --> HVXD
    DAT -- hạng CE đạt --> CE
```

- **Đào tạo**: theo dõi học viên từ lúc vào khóa → đủ điều kiện → kiểm tra → cấp giấy → sát hạch.
- **Xăng dầu**: trích chi phí xăng dầu cho giáo viên dựa trên hóa đơn và số học viên đã dạy.
- Học viên **đạt DAT** tự động chuyển sang Xăng dầu (xem [mục 5](#5-đồng-bộ-đào-tạo-dat--xăng-dầu)).

---

## 2. Phân quyền

Vào **Người dùng → Nhóm quyền**, tick quyền cho nhóm, rồi gán nhóm cho tài khoản. Menu và nút bấm chỉ hiện đúng phần được tick.

**Đào tạo** — mỗi mục (Khóa, Học viên, Xe, Giáo viên, Lý thuyết, Cabin, Hình, DAT, Tổng hợp, Ngày kiểm tra, Kết quả, HTKH, Sát hạch) có 5 quyền:

| Quyền | Cho phép |
|---|---|
| Tra cứu | Xem danh sách, lọc |
| Sửa | Thêm / sửa, bấm "Đạt" thủ công, tạo giấy |
| Xóa | Xóa dòng / xóa mục đã chọn |
| Import | Nhập file Excel |
| Xuất excel | Tải file Excel |

Quyền nhanh: **Quản trị đào tạo** (`daotao_man`) = toàn quyền + Tổng quan, Nhật ký, Sao lưu, Cấu hình cổng GV. **Tra cứu mọi mục** = chỉ xem + xuất.

**Xăng dầu**:

| Quyền | Cho phép |
|---|---|
| Xem và quản lý | Toàn quyền |
| Kế toán | Import, xem, kiểm tra, xuất bảng kê theo GV (không duyệt, không xóa, không cấu hình) |
| Xem / Import / Duyệt / Xóa / Cấu hình | Cấp riêng từng chức năng |

---

## 3. QL Đào tạo

### 3.1 Quy trình chuẩn

```mermaid
flowchart TD
    A[1. Import Khóa] --> B[2. Import Giáo viên, Xe]
    B --> C[3. Import Học viên<br/>BC1 hoặc file mẫu]
    C --> D[4. Import kết quả:<br/>Lý thuyết, Cabin, DAT]
    D --> E[5. GV nhập giờ/km Hình<br/>trên cổng GV hoặc admin nhập]
    E --> F{6. Tổng hợp<br/>Đủ điều kiện?}
    F -- Chưa --> D
    F -- Đủ --> G[7. Ngày kiểm tra:<br/>thêm HV đủ ĐK, xuất DS]
    G --> H[8. Import kết quả kiểm tra]
    H --> I[9. Số giấy HTKH:<br/>tạo + in giấy]
    I --> J[10. Import kết quả sát hạch]
```

> File mẫu import có sẵn trong thư mục `mau_import_daotao/` (xem `HUONG_DAN.txt`).
> Mọi trang import đều hỗ trợ **.xlsx / .xls**, **kéo-thả nhiều file**, và nút **Xem trước** (không ghi dữ liệu).

### 3.2 Khóa đào tạo

- **Import khóa**: cột Mã khóa, Tên khóa, Hạng, Ngày khai giảng, Ngày mãn khóa, Hệ đào tạo. Trùng mã khóa → cập nhật.
- Khóa cũng **tự tạo** khi import học viên / DAT / cabin có cột (hoặc tiêu đề) "Mã khóa".
- Lọc theo từ khóa, hạng, hệ, khoảng ngày khai giảng. Chọn khóa → **Xóa mục đã chọn** (tự sao lưu trước, xóa kèm dữ liệu học viên của khóa).

### 3.3 Học viên

- **Import học viên**: file BC1 hoặc file mẫu. Có cột Khóa/Hạng thì không cần chọn khóa; không có thì **chọn khóa** trước khi import.
- Cột **Giáo viên** trong file sẽ tự gắn học viên với giáo viên.
- Danh sách hiện tiến độ **LT / Cabin / Hình / DAT** và nhãn **Đủ ĐK / Chưa đủ**. Bấm vào từng nhãn để mở đúng mục đó.
- Ô CCCD tự bỏ dấu cách, dấu chấm; báo đỏ nếu không đủ 9/11/12 số.

### 3.4 Giáo viên & Xe tập lái

- **Giáo viên**: import theo file mẫu `01 GV_MAU` (tiêu đề ở dòng 2). Trùng CCCD → cập nhật. Có cột Ghi chú. Nút **Reset mật khẩu** đưa mật khẩu cổng GV về = CCCD.
- **Xe tập lái**: import theo file `imp_XE-MAU` (tiêu đề dòng 2). Danh sách cảnh báo **sắp hết hạn / hết hạn** giấy phép xe tập lái và GCN kiểm định. Có trạng thái **Thanh lý**.

### 3.5 Lý thuyết · Cabin · Thực hành hình · DAT

Bốn mục cùng cách dùng: **chọn khóa** (hoặc nhập tên/CCCD để tra toàn bộ) → lọc theo giáo viên / trạng thái → xem / import / xuất Excel.

| Mục | Nguồn dữ liệu | Điều kiện Đạt |
|---|---|---|
| Lý thuyết | Import theo từng môn (mỗi file 1 môn, hệ thống tự nhận môn theo tên file, chọn lại được). **Mô phỏng tính chung trong Lý thuyết** | Đạt đủ các môn |
| Cabin | Import file cabin (.xls). Mã khóa đọc từ phần đầu file | Kết quả Đạt |
| Thực hành hình | GV nhập trên cổng GV, hoặc admin nhập nhanh | Theo giờ/km từng hạng (bảng dưới) |
| DAT | Import file DAT (DSPH) hoặc thêm phiên tay | Đủ giờ, km, giờ đêm, giờ tự động theo hạng |

**Ngưỡng Thực hành hình** (đạt tự động khi *trên* giờ tối thiểu **và** *từ* km tối thiểu; hệ thống **không cho lưu** nếu vượt mức tối đa):

| Hạng | Giờ (tối thiểu → tối đa) | Km (tối thiểu → tối đa) |
|---|---|---|
| B11 / B1 | > 34 → 36 | ≥ 120 → 130 |
| C1 | > 35 → 37 | ≥ 115 → 120 |
| C / CE | > 7 → 8 | ≥ 15 → 20 |

**Đạt thủ công**: tick học viên → **Đạt mục đã chọn**, hoặc **Đạt toàn bộ** (cả khóa). Bấm ✕ cạnh nhãn "Xác nhận tay" để bỏ.

> Ở **DAT**, mỗi lần học viên được tính là đạt (import, thêm phiên, đạt thủ công), hệ thống tự đẩy học viên sang **Học viên XD** (xem [mục 5](#5-đồng-bộ-đào-tạo-dat--xăng-dầu)).

### 3.6 Tổng hợp

- Chọn khóa → bảng đủ cột: Lý thuyết · Cabin · Hình (giờ/km) · DAT (A/B/C/D, km) · **Kết luận**.
- Ô chưa đạt tô đỏ, ghi rõ **thiếu gì** (vd "Pháp luật – Phần 2: chưa đạt", "Thiếu 3 giờ đêm").
- **Xuất Excel** để gửi đi kiểm tra.

### 3.7 Ngày kiểm tra & Kết quả đào tạo

```mermaid
flowchart LR
    A[Tạo ngày kiểm tra] --> B{Thêm học viên}
    B -->|Thêm HV đủ ĐK của khóa| C[DS kỳ kiểm tra]
    B -->|Thêm cả khóa| C
    B -->|Nhập tay / Import DS<br/>khóa cũ| C
    C --> D[Xuất Excel<br/>tất cả / đủ / chưa đủ ĐK]
    D --> E[Import file kết quả kiểm tra]
    E --> F[Kết quả đào tạo]
```

- **Import kết quả kiểm tra**: tiêu đề dòng 6 (Số TT, Họ tên, Năm sinh, CCCD, Hạng, Mã khóa, …, Điểm LT, LT, Điểm Hình, Hình, Điểm Đường, Đường, Kết luận). Ghép theo **CCCD**; học viên khóa cũ chưa có sẽ được thêm.
- **Tra cứu**: theo ngày, CCCD, họ tên, mã khóa.
- **Kết quả đào tạo**: gộp tiến độ đào tạo + kết quả kiểm tra mới nhất của từng học viên.

### 3.8 Số giấy HTKH (giấy xác nhận hoàn thành)

1. Chọn **ngày kiểm tra** → chọn **khóa** (hoặc tất cả khóa trong kỳ).
2. Kiểm tra danh sách học viên **thi đạt**.
3. Nhập **Người ký**, **Ngày ký** (`dd/mm/yyyy`), **Chức danh** (KT. Giám đốc / Phó Giám đốc hoặc Giám đốc), kiểu nâng hạng CE (C/D/D2 lên CE).
4. Bấm **Tạo giấy xác nhận** → rồi **In / Lưu PDF**, **Xuất Word** hoặc **Xuất Excel** (mỗi học viên 1 trang A4).

Quy tắc in:
- **Số giấy** tự sinh `<Mã HV>-<hạng>` (vd `79044-20260828-000014-CE`), luôn in liền, không dấu cách quanh `-`. Có thể sửa tay hoặc import số giấy.
- Hạng in trên giấy: B11 → "B - Học xe chuyển số tự động (bao gồm xe điện)", B1 → "B - Học xe chuyển số cơ khí (số sàn)", C → "B lên C", CE → theo kiểu nâng hạng đã chọn.
- **Họ tên in hoa**, tên trung tâm màu đỏ, ngày ký trên 1 dòng.
- Giấy **chỉ in nội dung** (không nền trống đồng) để in lên **phôi có sẵn**; lề trên 3,5 cm.
- Chỉ in học viên **đã được tạo giấy**.

### 3.9 Kết quả sát hạch

- Import file kết quả sát hạch (ghép theo CCCD, trùng khóa + CCCD → cập nhật): Ngày sát hạch, Số GPLX, Kết quả.
- **Tra cứu** theo CCCD / họ tên / số GPLX / mã khóa. Xuất Excel theo khóa.

### 3.10 Cổng giáo viên

Địa chỉ: `https://<tên miền>/cong-giao-vien`

```mermaid
sequenceDiagram
    participant GV as Giáo viên
    participant P as Cổng GV
    participant A as Admin
    GV->>P: Đăng nhập CCCD + mật khẩu (mặc định = CCCD)
    P-->>GV: DS học viên của mình (lọc khóa / tên)
    GV->>P: Nhập giờ/km Thực hành hình
    P-->>GV: Tự xét Đạt / báo thiếu, ghi lịch sử 30 ngày
    A->>P: Cấu hình cổng GV: bật/tắt cột, cho phép nhập Hình
```

- GV chỉ **xem** Lý thuyết / Cabin / DAT, chỉ được **nhập Thực hành hình** (nếu admin cho phép).
- Quên mật khẩu: tự đặt lại bằng CCCD + ngày sinh/SĐT đã khai, hoặc admin bấm **Reset mật khẩu**.

### 3.11 Tổng quan · Nhật ký · Sao lưu

- **Tổng quan**: số khóa, học viên, tỷ lệ đạt từng phần.
- **Nhật ký thao tác**: ai import / xóa / sửa gì, lúc nào.
- **Sao lưu & khôi phục**: hệ thống **tự sao lưu trước mỗi lần import/xóa**. Vào đây để tải về hoặc khôi phục (khôi phục cũng tự sao lưu trạng thái hiện tại trước). Xóa 1 dòng có nút **Hoàn tác** trong 5 phút.

---

## 4. Xăng dầu (XD)

### 4.1 Quy trình chuẩn

```mermaid
flowchart TD
    C[Cấu hình định mức & mức chi] --> I1[Import Hóa đơn XD]
    C --> I2[Học viên XD:<br/>import file hoặc tự đồng bộ từ DAT]
    I1 & I2 --> L[Lọc thanh toán<br/>gom theo giáo viên]
    L --> K[Kế toán kiểm tra<br/>Chưa kiểm tra → Đã kiểm tra]
    K --> D[Quản lý duyệt<br/>Chờ duyệt → Đã thanh toán]
    D --> X[Xuất bảng kê / tổng hợp GV]
    D -. Hủy ngày này .-> L
```

**Cách tính**: với mỗi giáo viên, hệ thống lấy **tổng tiền hóa đơn hợp lệ chưa quyết toán**, rồi đi lần lượt từng học viên chưa thanh toán (theo khóa A–Z, thứ tự import). Mỗi học viên "tiêu" một **định mức** theo nhóm (BT/CK/DAT) và hạng. Còn đủ tiền thì học viên được trích; không đủ thì bỏ qua học viên đó, xét tiếp học viên sau.

### 4.2 Cấu hình định mức

- **Định mức XD** (số tiền hóa đơn cần cho 1 học viên): theo nhóm **BT / CK / DAT**; CK và DAT có thể đặt riêng theo hạng **bss, btd, c1, c, ce**. Ô để trống thì dùng mức chung của nhóm.
- **Mức chi** cho mỗi học viên theo nhóm.
- **Điều chỉnh cá nhân**: tìm học viên theo CCCD → đặt định mức riêng.

### 4.3 Hóa đơn XD

- **Import hóa đơn**: file tổng hợp hóa đơn (đọc đúng sheet "HĐơn" nếu file có nhiều sheet). Cột chính: Số HĐ, Ngày, Thông tin bán hàng, Chi tiết, Số tiền, Biển số, Giáo viên, **Hợp lệ** (`x` = hợp lệ), Note 1, Note 2.
- Tick **Đồng bộ lại** khi import lại cùng kỳ để sửa cờ hợp lệ / note cho hóa đơn **chưa quyết toán** (không tạo trùng).
- **Bộ lọc**: từ khóa (mã HĐ / tên GV), **Giáo viên**, **Kỳ**, ngày HĐ, ngày kế toán kiểm tra.
- Mỗi dòng: nút kiểm tra ✔, bật/tắt **hợp lệ** 👍, xóa.
- **Chọn nhiều → Xóa mục đã chọn** (hóa đơn đã quyết toán không có ô chọn và không bị xóa).
- Chỉ hóa đơn **Hợp lệ** mới được tính khi lọc thanh toán.

### 4.4 Học viên XD

- Nguồn: **Import học viên** (file .xlsx/.xls, cột Họ tên, Khóa, Ngày sinh, CCCD, Người nộp, Giáo viên, Nhóm…) hoặc **tự đồng bộ từ DAT**. Có nút **Đồng bộ HV đạt DAT** để chạy tay.
- **Mẫu BC1 có tài chính**: nhận `Số CMND/HC`, `Hệ đào tạo` (BT/CK/DAT), `Giáo viên`, `Người nộp hồ sơ` cùng 6 cột `Số tiền học phí`, `Miễn giảm`, `Số tiền đã đóng`, `Số tiền còn thiếu`, `Số tiền tạm ứng`, `Thanh toán Lương`. Import trực tiếp vào XD, **không tạo khóa/hồ sơ Đào tạo**. Với học viên mới, nhập khóa XD ở form nếu file thiếu cột Khóa; nhóm lấy từ Hệ đào tạo hoặc lựa chọn trên form. Học viên mới phải có giáo viên.
- Mẫu tài chính luôn **xem trước rồi xác nhận**. Dùng kết quả công thức đã lưu sẵn (kể cả tham chiếu ngoài); công thức thiếu kết quả hoặc lỗi Excel sẽ được báo rõ. Các khoản tiền là **nguyên đồng VNĐ**, không nhân 1.000 và không tự tính lại từ các cột khác.
- **Import lại mẫu tài chính/ngày nộp hồ sơ**: ghép theo CMND/CCCD (có đối chiếu số 0 đầu), cập nhật thông tin và tiền nguồn; **nhóm BT/CK/DAT lấy theo cột Hệ đào tạo/Nhóm trong Excel cho mọi hồ sơ, kể cả đã thanh toán hoặc đồng bộ DAT**. Cột/ô nhóm trống giữ nhóm cũ; lựa chọn nhóm mặc định chỉ dùng cho học viên mới. Nhóm khác BT/CK/DAT báo lỗi và chặn toàn bộ file. Xem trước hiển thị nhóm cũ → mới và số hồ sơ đổi nhóm. Nhóm mới dùng cho tính toán XD về sau, không tính lại tiền đã thanh toán. Giữ nguyên khóa, liên kết DAT, ngày/số tiền TT XD, bảng kê, định mức và cờ duyệt/kiểm tra. Cột tiền vắng mặt giữ giá trị cũ, ô trống trong cột hiện diện nhập là 0. Giáo viên trống giữ giá trị cũ; giáo viên hồ sơ DAT hoặc đã thanh toán/duyệt/thuộc bảng kê được giữ nguyên. Lưu bản sao trong `backups/` trước khi ghi; toàn bộ file thành công hoặc không lưu dòng nào.
- Sáu khoản tiền nguồn hiển thị trong danh sách học viên (nhãn **Nguồn · VNĐ**) và được thêm vào cuối file xuất Excel, độc lập với thanh toán XD; **không tự tạo giao dịch học phí, tạm ứng hoặc lương**.
- **Ngày nộp hồ sơ**: nhận cột cùng tên trong XLS/XLSX, định dạng `dd-mm-yyyy` (như Excel: `10-07-2026` là ngày 10 tháng 7), `dd/mm/yyyy`, `yyyy-mm-dd` hoặc ngày số Excel; lưu riêng và hiển thị trong xem trước, danh sách học viên, xuất Excel. Có thể import chỉ ngày mà không có 6 cột tiền. Ô trống/cột vắng mặt giữ ngày cũ; hồ sơ mới chưa có ngày lưu trống. **Ngày nhận Hồ sơ không phải Ngày nộp hồ sơ**. Ngày không tồn tại hoặc công thức có kết quả lỗi `#REF!` được báo theo dòng/cột và chặn toàn bộ file; cần sửa công thức hoặc dán ngày hợp lệ dưới dạng giá trị rồi lưu file.
- Công cụ local: `php scripts/import_xd_roster.php --local-db --course='201-B(SS)' /duong-dan/file.xls` chỉ xem trước và in tổng tiền/số dòng, không in thông tin cá nhân. Sau khi kiểm tra, thêm `--apply` để sao lưu, ghi và đối chiếu dữ liệu. Công cụ yêu cầu `MYSQLHOST` là `db`, `localhost` hoặc `127.0.0.1`; không dùng để import production. Kiểm thử: `php scripts/regression_xd_roster.php --local-db` (bảng tạm, không sửa học viên thật).
- **Bộ lọc**: từ khóa, **Nhóm**, **Khóa**, **Giáo viên**, trạng thái, khoảng ngày thanh toán. Xuất Excel theo đúng bộ lọc.
- Mỗi dòng: bấm nhãn trạng thái để chuyển **đã / chưa thanh toán**; nút ✎ sửa **Nhóm / Giáo viên** (chỉ khi chưa thanh toán).
- **Chọn nhiều → Xóa mục đã chọn**, hoặc **Xóa theo giáo viên**. Học viên **đã thanh toán / đã vào bảng kê luôn được giữ lại**.

### 4.5 Lọc thanh toán (kiểm tra → duyệt → bảng kê)

```mermaid
stateDiagram-v2
    [*] --> ChuaKiemTra: Có HĐ + HV chưa thanh toán
    ChuaKiemTra --> DaKiemTra: Kế toán bấm Kiểm tra
    DaKiemTra --> ChuaKiemTra: Hủy kiểm tra
    DaKiemTra --> DaThanhToan: Quản lý bấm Duyệt
    DaThanhToan --> ChuaKiemTra: Hủy ngày này
    DaThanhToan --> [*]: Xuất bảng kê
```

| Màn hình | Ai dùng | Thao tác |
|---|---|---|
| **Chưa kiểm tra** | Kế toán / Quản lý | Xem chi tiết GV (hóa đơn + học viên dự kiến trích) → **Kiểm tra** |
| **Chờ duyệt** | Quản lý (kế toán chỉ xem) | **Duyệt** từng GV hoặc **Duyệt tất cả** |
| **Đã thanh toán** | Tất cả | Xem theo GV + ngày; **Hủy ngày này** để hoàn tác đúng ngày đó |

- Xuất **bảng kê theo GV** chỉ được khi hóa đơn của GV đó **đã kiểm tra**.
- Xuất được: bảng kê từng GV, tổng hợp đã duyệt, tổng hợp GV, toàn bộ bảng kê, danh sách học viên.

### 4.6 Danh sách CE

- Học viên **hạng CE đạt DAT** tự vào đây (tách riêng theo khóa, không vào quyết toán XD chung).
- Hóa đơn CE: nhập tay hoặc import (tải **file mẫu** tại trang). Cột: Mã khóa CE, Số hóa đơn, Ngày hóa đơn, Số tiền, Giáo viên, Kỳ. File có dòng lỗi / trùng thì **từ chối cả file**.

---

## 5. Đồng bộ Đào tạo (DAT) → Xăng dầu

```mermaid
flowchart TD
    T[Import DAT / Thêm phiên / Đạt mục đã chọn /<br/>Đạt toàn bộ / Import học viên /<br/>Nút Đồng bộ HV đạt DAT] --> S{Đạt DAT?<br/>đủ ngưỡng hoặc Đạt tay}
    S -- Không --> X[Bỏ qua]
    S -- Có --> E{Đã có trong<br/>Học viên XD?<br/>so theo CCCD}
    E -- Chưa --> N[Thêm mới:<br/>B1/B11/C1 hệ BT: nhóm BT<br/>Trường hợp khác: nhóm DAT<br/>Khóa = mã khóa đào tạo<br/>GV = GV bên Đào tạo<br/>Chưa thanh toán]
    E -- Có --> K[Cập nhật mã khóa;<br/>sửa DAT thành BT cho hồ sơ đồng bộ cũ<br/>đúng liên kết B1/B11/C1 hệ BT;<br/>giữ lịch sử thanh toán]
    N --> L[Lọc thanh toán như cũ]
```

Lưu ý — hệ thống **không** tự làm các việc sau:
- Đổi giáo viên bên Đào tạo **không** tự đổi ở học viên XD đã có → sửa tay ở Học viên XD.
- Bỏ Đạt DAT / xóa phiên DAT **không** xóa học viên đã sang XD.
- Sửa bên XD **không** cập nhật ngược về Đào tạo.
- Cột **Khóa** của học viên đồng bộ là **mã khóa đào tạo** (dùng tên khóa nếu mã trống).
- **Sửa nhóm đồng bộ cũ:** bấm **Đồng bộ HV đạt DAT** để sửa DAT → BT cho hồ sơ B1/B11/C1 hệ BT do luồng đồng bộ cũ tạo. Chỉ sửa khi nguồn là `Đồng bộ DAT` và ID liên kết khớp học viên Đào tạo; không ghi đè nhóm CK, hồ sơ import riêng hoặc liên kết khác. Hệ đào tạo ưu tiên học viên, nếu trống dùng hệ của khóa. Sửa cả hồ sơ đã thanh toán nhưng giữ nguyên số tiền/ngày thanh toán, bảng kê, định mức và cờ duyệt/kiểm tra; nhóm mới chỉ ảnh hưởng tính toán về sau. Thông báo đồng bộ hiển thị số hồ sơ đã sửa. Đồng bộ lại không tạo trùng hay sửa lại hồ sơ đã đúng.

---

## 6. Thao tác chung

| Thao tác | Cách làm |
|---|---|
| Lọc | Chọn điều kiện → **Lọc**; **Bỏ lọc** để xóa điều kiện. Bộ lọc được giữ khi sửa/xóa/chuyển trang |
| Xuất Excel | Nút **Xuất Excel** xuất đúng dữ liệu đang lọc |
| Chọn nhiều | Tick ô đầu dòng hoặc ô ở tiêu đề (chọn cả trang) → nút **… mục đã chọn** |
| Import nhiều file | Kéo-thả nhiều file vào khung; hệ thống chạy lần lượt và báo từng file thành công / lỗi |
| Xem trước import | Bấm **Xem trước**: hiện số dòng thêm / cập nhật / lỗi, chưa ghi dữ liệu (sau đó chọn lại file và bấm Import) |
| Thông báo lỗi import | Đọc **từng dòng lỗi** trong thông báo (vd "Dòng 15: CCCD chưa có"), không chỉ dòng tiêu đề |

---

## 7. Câu hỏi thường gặp

**Import học viên báo "thiếu khóa"?** File không có cột Khóa → chọn khóa ở ô chọn trước khi import.

**Học viên đạt DAT nhưng không thấy bên XD?** Kiểm tra: học viên có CCCD chưa, hạng có thuộc B1/B11/C1/C không (CE nằm ở Danh sách CE), hoặc đã có sẵn trong XD. Bấm **Đồng bộ HV đạt DAT** để chạy lại.

**Hóa đơn hiện "Không hợp lệ" sai?** Import lại file với tick **Đồng bộ lại** (chỉ sửa hóa đơn chưa quyết toán), hoặc bấm nút 👍 để đổi tay.

**Duyệt nhầm?** Vào **Lọc thanh toán → Đã thanh toán** → **Hủy ngày này** đúng giáo viên + ngày.

**Lỡ xóa / import sai dữ liệu đào tạo?** Vào **Sao lưu & khôi phục**, chọn bản sao lưu ngay trước thao tác đó → **Khôi phục**.

**Giấy HTKH in lệch phôi?** In từ **In / Lưu PDF** với khổ A4, tỉ lệ 100%, không chọn "Fit to page"; nếu cần dịch chuyển, báo để chỉnh lề.

**GV không đăng nhập được cổng?** Mật khẩu mặc định = CCCD; admin bấm **Reset mật khẩu** ở mục Giáo viên.
