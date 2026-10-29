## Context

The supplied XLS files have 22 identical headers but no course or XD group columns. Existing XD imports require a teacher and BT/CK/DAT group, match normalized CCCD, and skip paid students. Fuel payments are not tuition or salary source amounts.

## Goals / Non-Goals

**Goals:** Read XLS/XLSX, retain six source money fields, preview before persistence, safely update existing identities, preserve financial history, and import the three samples locally after preview.

**Non-Goals:** Training imports, new tuition transactions, automatic salary/fuel settlement, production changes, and importing additional personal information not requested.

## Decisions

- For new XD students missing both file and form course, read training students by CCCD variants and compare names case-insensitively with collapsed whitespace, preserving accents. Resolve the unique course code (or course name if code is blank); reject multiple distinct courses. Never change training records or derive the group from training. Existing XD courses and explicit file/form courses retain priority.

- Extend the existing XD importer rather than introduce another import destination.
- Store six explicit decimal source fields on XD students, independent of fuel-payment fields.
- Use exact header aliases and integer VND validation; do not reuse fuel helpers that multiply small numbers by 1,000.
- Files without course/group columns use an explicit upload course and group for new students. Existing courses remain authoritative, but nonblank BT/CK/DAT groups from Excel replace existing groups even for settled or DAT-origin students. Blank/missing groups retain existing groups regardless of the new-student form default. Preview reports old/new groups and change counts; existing settlement amounts/dates are never recalculated.
- Extract reusable parsing and transactional persistence for web preview and CLI validation/import. A preview never writes students or transactions.
- Reimport financial metadata even for paid students, preserving all settlement, approval, batch and DAT-origin linkage fields. Missing financial columns preserve existing values.
- Validate all rows before writing, lock identities during persistence, and back up affected records before changing local data.
- Store optional `ngay_nop_ho_so` as a nullable SQL DATE. Accept Vietnamese dates separated by `/` or `-` (day first, including Excel text such as `10-07-2026`), ISO dates and Excel serial dates; reject impossible dates, mixed separators and cached formula errors. Blank or missing source dates preserve existing values. `Ngày nhận Hồ sơ` is not an alias for submission date. Route date-bearing files through preview even without financial columns.
- Repair the legacy hardcoded DAT group during normal training synchronization only when the current B1/B11/C1 training system resolves to BT, the existing XD group is DAT, and `user_tao='Đồng bộ DAT'` with `thu_tu_file` matching the training student ID. Preserve CK/manual/imported groups and every settlement/linkage field. Report repair counts; repeated sync is idempotent.

## Risks / Trade-offs

- [Unknown money units] → Treat source values as VND, inspect preview totals before import, and do not infer payment status from amounts.
- [Group changes affect future XD calculations] → Show changes before confirmation and preserve historical settlement amounts/dates, batch, approvals and DAT linkage; invalid supplied groups abort the import.
- [Repeat import duplicates] → Match normalized identity variants; reject ambiguous or repeated identities.
- [Concurrent settlement] → Lock existing rows and preserve payment history.

## Migration Plan

Add six columns idempotently; keep old values and legacy import format intact. Back up affected local records before import. Rollback restores affected records from backup; additive columns can remain without changing existing behavior.

## Open Questions

The local preview must establish missing teacher/group/course values and conflicting identities before any actual import.
