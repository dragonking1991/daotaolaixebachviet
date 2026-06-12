# Tasks

- [x] Add `nhan-vien` product type configuration and admin menu visibility.
- [x] Extend employee import in `admin/sources/import.php` to use header-based mapping and persist full row JSON in `options2`.
- [x] Update admin employee edit/list views to expose CCCD, department, and title.
- [x] Add public route and template for employee lookup.
- [x] Add AJAX read endpoint for employee detail.
- [x] Add AJAX update endpoint for replacing CCCD with uniqueness validation.
- [x] Add request validation and user-facing error messages for empty/invalid/duplicate CCCD.
- [x] Verify imports from `.xls`, `.xlsx`, and `.xlsm` files that contain employee payroll columns.
- [x] Verify lookup after CCCD change uses the new CCCD and no longer uses the old CCCD.
- [x] Decide and implement the minimum safeguard required for public CCCD update.
