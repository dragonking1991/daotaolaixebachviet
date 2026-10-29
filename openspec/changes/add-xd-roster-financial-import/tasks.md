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
