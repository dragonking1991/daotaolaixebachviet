# Proposal: Employee Excel Import And Public Lookup

## Why

The current repo already supports Excel import and CCCD-based lookup, but only for three fixed product types: `gplx`, `gxn`, and `qr`.

The requested employee flow needs capabilities that do not exist yet:

1. Admin imports employee data from salary-like Excel files with many columns.
2. Admin can manage an explicit `cccd` field as a string, either imported from Excel or filled later.
3. Public users can search employee details by CCCD.
4. Public users can change the CCCD stored for their employee record so future lookups use the new value.

## Current Constraints In This Repo

1. Import logic in `admin/sources/import.php` assumes a fixed column order and maps only a small set of fields.
2. Public lookup in `ajax/tracuu.php` is hard-wired to the existing record types and renders only their current detail set.
3. `table_product` has a usable `cccd` column and an `options2` text column that can hold structured JSON, which makes it the smallest existing storage surface for employee detail rows.

## Proposed Change

Introduce a new importable product type named `nhan-vien` and treat each employee row as one `table_product` record.

Use the existing normalized fields for stable identifiers:

1. `tenvi`: employee full name.
2. `cccd`: searchable citizen ID string.
3. `hang`: department name.
4. `khoa`: position/title.
5. `ngaysinh`: if present in the file.

Store the full imported row in `options2` as JSON so salary/detail columns can vary by file without forcing an immediate schema migration.

Add a public employee lookup page and an update action that allows changing CCCD for the matched employee record.

## Scope

### Admin

1. Add `nhan-vien` to `libraries/type/config-type-product.php` as an import-enabled type.
2. Extend `admin/sources/import.php` to support header-based import for employee Excel files.
3. Allow CCCD to come from either:
   - a recognized Excel header, or
   - manual edit after import in the admin product form.
4. Show employee department/title/CCCD in admin listing or item detail so imported data is reviewable.

### Public

1. Add a dedicated route/page for employee lookup.
2. Default search key is CCCD.
3. Render all imported employee detail fields from `options2` in a readable detail view.
4. Provide a controlled action to replace the stored CCCD with a new one.

## Non-Goals

1. No payroll calculations are proposed in this change.
2. No monthly history/versioning is included in the first pass.
3. No bulk CCCD reconciliation against another HR source is included.

## Risk And Recommendation

Public salary-like detail plus public CCCD update is sensitive.

Minimum recommended safeguard for the update action:

1. Require the current CCCD lookup to succeed first.
2. Require one secondary confirmer already stored in the row, preferably `ngaysinh` or another stable field, before saving the new CCCD.

If the business wants update-by-CCCD only, that should be treated as an accepted security risk and documented.
