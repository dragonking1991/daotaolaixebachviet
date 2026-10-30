## Why

The XD student importer does not accept the supplied XLS roster layout or retain its six financial columns. Staff need to reimport rosters without changing training records or existing fuel-payment history.

## What Changes

- Standardize course dropdowns throughout training and XD with code, name, student counts and searchable suggestions, using existing Select2 without changing selected values or data.

- Reuse a training course for new XD students missing a course when both name and CCCD match uniquely; do not guess between conflicting courses.

- Accept the existing XD XLSX layout and the supplied 22-column XLS roster layout.
- Retain tuition, discount, tuition paid, tuition outstanding, advance, and salary-payment source amounts separately from XD fuel payments.
- Provide preview before import and safe repeat imports matched by normalized identity.
- Refresh existing student groups from explicit BT/CK/DAT values in Excel, including settled students, without recalculating historical settlements.
- Preserve BT/Bổ túc and CK/Chính khóa for DAT-qualified B1/B11/C1 students and repair linked legacy DAT-origin groups, without touching settlement history, independently imported groups or other-rank mapping.
- Show the imported amounts in a separate read-only source section of training finance instead of the XD student list; preserve XD storage/import and existing financial transactions.
- Retain optional submission dates from `Ngày nộp hồ sơ`, validate dates and show them in preview, student lists and exports without substituting receipt dates.
- Preview and import the three supplied files into local data only, preserving existing payment history.

## Capabilities

### New Capabilities
- `xd-roster-financial-import`: Import and inspect financial source amounts from XD rosters with safe previews and repeat imports.

### Modified Capabilities

None.

## Impact

XD student schema, upload/parser/persistence, student UI, directly related documentation and regression tests. No imports into training courses, no automatic financial transactions, and no deployment or production import.
