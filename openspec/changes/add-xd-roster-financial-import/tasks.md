## 1. XD roster import

- [x] 1.1 Add six additive financial source columns without changing fuel settlement.
- [x] 1.2 Implement XLS/XLSX roster parsing, exact VND amounts, identity matching, preview, and transactional reimport.
- [x] 1.3 Add upload controls and student financial display; update related documentation.

## 2. Validation and local reimport

- [x] 2.1 Add and run regressions for parsing, invalid amounts, repeat imports, missing columns, and payment-history preservation.
- [x] 2.2 Preview the three sample files and resolve required group/course or identity conflicts.
- [x] 2.3 Back up and import the three files locally; verify totals, record counts, and unchanged payment history.

## 3. Submission date

- [x] 3.1 Add optional submission date storage and validated XLS/XLSX import without substituting receipt dates or clearing existing dates on blank input.
- [x] 3.2 Show submission dates in preview, student list and Excel export; document source errors.
- [x] 3.3 Verify date formats, invalid inputs, repeat imports and settlement preservation; refresh the ZIP in the source root.

## 4. Excel text date compatibility

- [x] 4.1 Accept dd-mm-yyyy text dates with day-first semantics, preserve existing formats and strict date validation, and test actual XLS/XLSX inputs.
- [x] 4.2 Update format guidance and refresh verified ZIP deliverables.

## 5. Excel group authority

- [x] 5.1 Update groups from explicit Excel values for existing students including settled/DAT-origin records; preserve absent groups and all settlement fields.
- [x] 5.2 Verify group changes, invalid-group rollback, preview and CLI verification; update guidance and ZIP.

## 6. Legacy training sync group repair

- [x] 6.1 Repair legacy DAT groups to BT for matching DAT-origin B1/B11/C1 students whose training system is BT; retain manual/imported groups and settlement history.
- [x] 6.2 Test paid/unpaid repairs, repeat synchronization and exclusions; correct the affected local student with backup and refresh delivery.

## 7. Training course fallback

- [x] 7.1 Resolve missing new-student XD courses by matching both name and normalized identity to training; reject conflicting courses and preserve explicit/existing courses.
- [x] 7.2 Test fallback, ambiguity, persistence and actual sample preview; update guidance and delivery.

## 8. Source financial display in training finance

- [x] 8.1 Move six read-only source columns from XD student list to a separate training finance section, including BT/unmatched students, course filtering, pagination and source export; leave storage/import and transaction eligibility unchanged.
- [x] 8.2 Test source display/export and no writes, existing finance/import regressions, and refresh delivery documentation/ZIP.

## 9. Searchable course selectors

- [x] 9.1 Standardize all training/XD course options with code, name and context-specific student counts, including CE, source finance and HTKH period courses.
- [x] 9.2 Enable existing Select2 search/suggestions by code/name with Vietnamese accent-insensitive matching, preserving values, placeholders, groups and submit behavior.
- [x] 9.3 Validate dropdown coverage, label/count providers, browser interactions and existing regressions; refresh documentation and ZIP.
- [x] 9.4 Show all options immediately on click, reset previous searches on reopening, and verify browser behavior and refreshed ZIP.
- [x] 9.5 Fix global Select2 initialization colliding with generated course containers; verify original placeholder labels, full list, search and repeated initialization in browser, and refresh delivery.
- [x] 9.6 Distinguish course name from code in training list, search XD course management by either metadata field (including legacy links), and verify real K13 search without changing stored identities.

## 10. CK synchronization and gap audit

- [x] 10.1 Preserve CK/Chính khóa and BT/Bổ túc for DAT-qualified B1/B11/C1 students; safely repair linked DAT-origin groups and report BT/CK counts separately, retaining other-rank behavior.
- [x] 10.2 Test new/legacy/paid records, aliases, student/course precedence, exclusions, missing DAT and repeat sync; audit local mapping gaps.
- [x] 10.3 Back up and repair only the reported local XD record 7684 linked to training record 1825, verify group-only difference, document remaining cases and refresh ZIP.
