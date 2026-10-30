## Context

The supplied XLS files have 22 identical headers but no course or XD group columns. Existing XD imports require a teacher and BT/CK/DAT group, match normalized CCCD, and skip paid students. Fuel payments are not tuition or salary source amounts.

## Goals / Non-Goals

**Goals:** Read XLS/XLSX, retain six source money fields, preview before persistence, safely update existing identities, preserve financial history, and import the three samples locally after preview.

**Non-Goals:** Training imports, new tuition transactions, automatic salary/fuel settlement, production changes, and importing additional personal information not requested.

## Decisions

- Use a shared course option label (`code — name · N HV`) for every training/XD course selector. Training counts use training rosters; XD/source/CE counts use their respective XD lists; HTKH period selectors count records in that period. Missing legacy names/codes are explicit, not inferred. Preserve DAT-pass suffixes.
- Enhance only training/XD course selects with the already bundled Select2, including nested CRUD fields and HTKH period-course keys. Search code/name with case- and Vietnamese-accent-insensitive substring matching, retain native values, optgroups, empty/unassigned options, required validation and change handlers. Do not allow arbitrary new course values.
- On every opening, clear the previous search and show the full scrollable course list immediately; typing is optional and only narrows the list.
- The global Select2 initializer targets only uninitialized `select.select2` elements, never the generated `.select2` containers. This preserves the course initializer's options, original empty-option labels and search placeholder.
- Keep stored code/name semantics unchanged. Display the training name as the course-list title with an explicitly labeled code underneath. XD course management searches both stored keys and training code/name through an EXISTS predicate, including legacy DAT links, without joining extra rows into roster aggregates.

- Display all six XD source amounts in a distinct read-only section of training finance, independently filtered by XD course and paginated, with a matching full filtered source export. Include all XD groups and students not linked to training; no DAT/CK eligibility filter applies to source inspection. Existing training transactions, eligibility, amounts and exports remain separate; XD storage, import preview and original XD spreadsheet export remain unchanged.

- For new XD students missing both file and form course, read training students by CCCD variants and compare names case-insensitively with collapsed whitespace, preserving accents. Resolve the unique course code (or course name if code is blank); reject multiple distinct courses. Never change training records or derive the group from training. Existing XD courses and explicit file/form courses retain priority.

- Extend the existing XD importer rather than introduce another import destination.
- Store six explicit decimal source fields on XD students, independent of fuel-payment fields.
- Use exact header aliases and integer VND validation; do not reuse fuel helpers that multiply small numbers by 1,000.
- Files without course/group columns use an explicit upload course and group for new students. Existing courses remain authoritative, but nonblank BT/CK/DAT groups from Excel replace existing groups even for settled or DAT-origin students. Blank/missing groups retain existing groups regardless of the new-student form default. Preview reports old/new groups and change counts; existing settlement amounts/dates are never recalculated.
- Extract reusable parsing and transactional persistence for web preview and CLI validation/import. A preview never writes students or transactions.
- Reimport financial metadata even for paid students, preserving all settlement, approval, batch and DAT-origin linkage fields. Missing financial columns preserve existing values.
- Validate all rows before writing, lock identities during persistence, and back up affected records before changing local data.
- Store optional `ngay_nop_ho_so` as a nullable SQL DATE. Accept Vietnamese dates separated by `/` or `-` (day first, including Excel text such as `10-07-2026`), ISO dates and Excel serial dates; reject impossible dates, mixed separators and cached formula errors. Blank or missing source dates preserve existing values. `Ngày nhận Hồ sơ` is not an alias for submission date. Route date-bearing files through preview even without financial columns.
- For DAT-qualified B1/B11/C1 students, map normalized BT/Bổ túc to BT and CK/Chính khóa to CK, preferring student system over the course fallback. Preserve DAT for other ranks (including CE), blank systems and unrecognized systems such as GTT; do not infer a business policy for them. Repair only existing DAT groups from `user_tao='Đồng bộ DAT'` with `thu_tu_file` matching the training student ID. Preserve existing BT/CK/manual/imported groups and every settlement/linkage field. Report BT and CK repairs separately; repeated sync is idempotent.

## Risks / Trade-offs

- [Unknown money units] → Treat source values as VND, inspect preview totals before import, and do not infer payment status from amounts.
- [Group changes affect future XD calculations] → Show changes before confirmation and preserve historical settlement amounts/dates, batch, approvals and DAT linkage; invalid supplied groups abort the import.
- [Repeat import duplicates] → Match normalized identity variants; reject ambiguous or repeated identities.
- [Concurrent settlement] → Lock existing rows and preserve payment history.

## Migration Plan

Add six columns idempotently; keep old values and legacy import format intact. Back up affected local records before import. Rollback restores affected records from backup; additive columns can remain without changing existing behavior.

## Open Questions

The local preview must establish missing teacher/group/course values and conflicting identities before any actual import.
