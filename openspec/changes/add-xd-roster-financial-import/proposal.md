## Why

The XD student importer does not accept the supplied XLS roster layout or retain its six financial columns. Staff need to reimport rosters without changing training records or existing fuel-payment history.

## What Changes

- Accept the existing XD XLSX layout and the supplied 22-column XLS roster layout.
- Retain tuition, discount, tuition paid, tuition outstanding, advance, and salary-payment source amounts separately from XD fuel payments.
- Provide preview before import and safe repeat imports matched by normalized identity.
- Refresh existing student groups from explicit BT/CK/DAT values in Excel, including settled students, without recalculating historical settlements.
- Repair legacy DAT-origin groups incorrectly stored as DAT for linked B1/B11/C1 training students in system BT, without touching settlement history or independently imported groups.
- Show the imported amounts in the XD student UI and document the supported formats.
- Retain optional submission dates from `Ngày nộp hồ sơ`, validate dates and show them in preview, student lists and exports without substituting receipt dates.
- Preview and import the three supplied files into local data only, preserving existing payment history.

## Capabilities

### New Capabilities
- `xd-roster-financial-import`: Import and inspect financial source amounts from XD rosters with safe previews and repeat imports.

### Modified Capabilities

None.

## Impact

XD student schema, upload/parser/persistence, student UI, directly related documentation and regression tests. No imports into training courses, no automatic financial transactions, and no deployment or production import.
