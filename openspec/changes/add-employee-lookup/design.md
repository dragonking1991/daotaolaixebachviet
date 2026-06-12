# Design: Employee Excel Import And CCCD Lookup

## Chosen Data Model

Use `table_product` with `type = 'nhan-vien'`.

Reason:

1. The repo already uses `table_product.cccd` as the public lookup key.
2. Admin import and product management already exist.
3. `options2` can store dynamic employee detail without adding many new columns.

## Record Shape

Suggested field mapping for `nhan-vien` records:

1. `tenvi`: employee full name.
2. `tenkhongdauvi`: slug from full name.
3. `cccd`: current lookup key, always stored as string.
4. `hang`: department.
5. `khoa`: job title.
6. `ngaysinh`: optional birth date if present.
7. `options2`: JSON object with all imported columns.

Suggested `options2` structure:

```json
{
  "source_headers": ["stt", "ho_va_ten", "chuc_vu", "cccd", "luong_chinh"],
  "detail": {
    "stt": "1",
    "bo_phan": "Bộ phận giáo viên",
    "ho_va_ten": "Vũ Giang Nam",
    "chuc_vu": "C",
    "so_ngay_lam_viec": "",
    "luong_chinh": "5681700",
    "thuong_le_tet": "0",
    "tong_thu_nhap": "30181700",
    "luong_thuc_nhan": "28776609"
  },
  "import_meta": {
    "file_name": "LUONG MAU.xlsm",
    "sheet_name": "BANG CAP LUONG",
    "imported_at": 1780963200
  }
}
```

## Import Strategy

### Header detection

Employee import should not rely on fixed column positions.

Instead:

1. Read row 1 as headers.
2. Normalize Vietnamese header names the same way the repo already normalizes import labels.
3. Detect aliases for stable fields:
   - name: `hovaten`, `ten`, `hoten`
   - cccd: `cccd`, `socccd`, `cancuoc`, `socancuoc`
   - department: `bophan`, `phongban`, `donvi`
   - title: `chucvu`, `vitri`
   - birth date: `ngaysinh`, `namsinh`
4. Persist every non-empty header/value pair into `options2.detail`.

### Upsert rule

Use this priority when matching existing employee records:

1. existing `cccd` if imported CCCD is present,
2. otherwise an explicit admin-edited employee id,
3. otherwise fallback match by exact name plus department only if business approves it.

For first implementation, safest rule is:

1. upsert by `cccd` when present,
2. insert a new record when `cccd` is empty,
3. let admin fill CCCD later.

## Public Lookup Flow

### Read

1. User enters CCCD on the employee lookup page.
2. AJAX endpoint fetches `table_product` where `type = 'nhan-vien'` and `cccd` matches exact string.
3. Response renders normalized fields first, then a detail list/table from `options2.detail`.

### Update CCCD

1. Detail view includes a `new_cccd` input.
2. Endpoint validates:
   - current record exists,
   - new CCCD is 11 to 12 digits or accepted business format,
   - new CCCD is not already used by another `nhan-vien` record.
3. Save new value to `table_product.cccd`.
4. Return the refreshed detail payload with the new CCCD.

## UI Notes

### Admin

1. Reuse the existing import screen under `com=import&type=nhan-vien`.
2. Add employee sample image or help text that explains expected headers.
3. Add manual CCCD edit in the product form for `nhan-vien`.

### Public

1. Do not mix employee lookup into the current GPLX/GXN/QR tabs.
2. Add a dedicated page so the result layout can focus on employee details.
3. Render a simple two-column detail table for all imported fields.

## Security Notes

If update-by-CCCD only is kept, anyone who knows a valid CCCD can rewrite it.

Preferred mitigation:

1. Require `ngaysinh` confirmation, or
2. require an employee code/second identifier from the imported row.
