## ADDED Requirements

### Requirement: Searchable consistent course selectors
All training and XD course selectors SHALL display course code, name and context-specific student count, explicitly marking missing legacy metadata. Search SHALL suggest existing options by code or name, case- and Vietnamese-accent-insensitively, without changing option values, optgroups, placeholders or submission behavior.

#### Scenario: Search course by name without accents
- **WHEN** a user types an unaccented portion of a Vietnamese course name
- **THEN** matching existing courses SHALL appear as suggestions and selection SHALL submit the original ID/key, never create a new course.

#### Scenario: Open the complete course list
- **WHEN** a user clicks a course selector, including after a previous search with no matches
- **THEN** it SHALL clear the search and immediately show all existing options in a scrollable dropdown without requiring typing or changing the selected value.

#### Scenario: Global initializer runs after course initialization
- **WHEN** the shared admin Select2 initializer runs after course selectors are enhanced
- **THEN** it SHALL initialize only uninitialized select elements, not generated containers, and preserve the original default label (such as `Tất cả khóa` or `Chọn khóa`), search placeholder and complete course options.

#### Scenario: Counts in specialized selectors
- **WHEN** users select courses in CE, source finance, training or HTKH periods
- **THEN** labels SHALL include counts from the respective student lists and maintain empty/unassigned options and existing DAT-pass information.

#### Scenario: Distinguish and search course metadata
- **WHEN** a course has code `79044K26C1005` and name `K13-C1`
- **THEN** the training list SHALL show the name as its title and explicitly label the code, and XD course management and course dropdowns SHALL find it using `k13` or its code without changing stored keys or multiplying student counts.
- **AND** XD course management SHALL also match training names obtained through valid legacy DAT student links.

### Requirement: Missing course lookup in training
For new XD students without file or form course, the importer SHALL match both CCCD variants and name (case-insensitive, collapsed whitespace, accents retained) against training records and use the unique course code, falling back to course name when code is blank. It SHALL not modify training or replace an explicit/existing XD course.

#### Scenario: Matching student with a unique course
- **WHEN** a new XD student's name and CCCD match training records resolving to one course
- **THEN** preview and persistence SHALL use that course without requiring a form default.

#### Scenario: No match or conflicting courses
- **WHEN** no usable course matches both name and CCCD, or matches resolve to different courses
- **THEN** import SHALL stop with a row-level message requesting an explicit XD course, without partial writes.

### Requirement: Legacy training synchronization group repair
The training sync SHALL map BT/Bổ túc to BT and CK/Chính khóa to CK for DAT-qualified B1/B11/C1 students, using normalized case/accent/whitespace and preferring the student system over a blank-system course fallback. It SHALL repair existing DAT groups only when the DAT-origin XD record links to that same training student ID, preserve all settlement and linkage fields, report BT/CK repair counts separately and remain idempotent.

#### Scenario: Existing legacy BT student
- **WHEN** a linked DAT-origin XD record has group DAT but its B1 training student has system BT
- **THEN** synchronization SHALL change only the group to BT (plus the existing course-code synchronization), including for settled records, without recalculating payment history.

#### Scenario: New or legacy CK student
- **WHEN** a DAT-qualified B1/B11/C1 student's effective system is CK or Chính khóa
- **THEN** a new XD record SHALL use CK and an existing correctly linked DAT-origin DAT record SHALL be repaired to CK, including settled records, without recalculating settlement fields or creating transactions.

#### Scenario: Missing DAT or different mapping scope
- **WHEN** a student has not passed DAT
- **THEN** synchronization SHALL neither add nor repair that student.
- **WHEN** the rank is outside B1/B11/C1, or its effective system is blank or unrecognized
- **THEN** synchronization SHALL retain the existing DAT mapping, including CE, without inferring CK/BT from another rank or unknown system.

#### Scenario: Independent or manually chosen group
- **WHEN** the existing XD record was independently imported, links to a different training student, or already has BT or CK
- **THEN** the legacy repair SHALL leave its group unchanged.

### Requirement: Supported roster formats
The XD importer SHALL accept the existing XLSX layout and the supplied XLS roster headers, including CMND/HC identity and the six financial source fields.

#### Scenario: Supplied XLS roster
- **WHEN** a supplied 22-column XLS roster is previewed
- **THEN** its students and all six amounts are parsed without creating training records.

### Requirement: Source money is separate from settlement
The importer MUST retain tuition, discount, tuition paid, tuition outstanding, advance and salary-payment source amounts independently from fuel payment history.

#### Scenario: Nonzero source salary payment
- **WHEN** a roster contains a salary-payment amount
- **THEN** it is stored as source metadata and does not mark a fuel payment as paid or create a transaction.

### Requirement: Safe repeated import
The importer SHALL match normalized identities, preserve existing settlement and DAT linkage fields, preserve absent source fields, and reject invalid or ambiguous data before writing.

#### Scenario: Existing settled student
- **WHEN** financial metadata is reimported for an existing settled student
- **THEN** the source amounts are refreshed without changing batch, approval, payment amounts, dates or training linkage.

#### Scenario: Missing optional amount column
- **WHEN** a source financial column is absent
- **THEN** the stored value is not cleared.

#### Scenario: Excel group overrides an existing group
- **WHEN** a matched student has group DAT and the roster explicitly provides BT, including for a settled or DAT-origin student
- **THEN** the importer SHALL preview and save BT while preserving course, payment amounts/dates, batch, approval flags, and DAT linkage.

#### Scenario: Absent or invalid group
- **WHEN** an existing student's group is blank or absent in the roster
- **THEN** the existing group SHALL remain unchanged even if the form has a default group for new students.
- **WHEN** a nonblank group is not BT, CK or DAT
- **THEN** import SHALL fail before saving any students.

### Requirement: Preview before local import
The system SHALL provide a write-free preview with new/update counts and source totals and SHALL import all valid rows atomically.

#### Scenario: Invalid row
- **WHEN** any row has invalid money, duplicate identity or unresolved required new-student group/course
- **THEN** the preview reports the error and the import writes no students.

### Requirement: Inspect retained amounts
Training finance SHALL expose all six XD source financial amounts in a read-only source section instead of the XD student list and distinguish them from training transactions and fuel settlement. XD storage/import SHALL remain unchanged.

#### Scenario: Staff inspect student
- **WHEN** staff view training finance source information
- **THEN** they can inspect each source amount with its correct label and VND unit.

#### Scenario: Source data outside transaction eligibility
- **WHEN** an XD source record is BT, not DAT-qualified, or has no training student
- **THEN** it SHALL remain visible in the source section without becoming selectable for transactions.

#### Scenario: Source course filtering and export
- **WHEN** staff filter a source course or export its source amounts
- **THEN** pagination SHALL preserve the filter and source export SHALL include all filtered records, all six amounts, and text CCCDs without updating data or creating transactions.

### Requirement: Submission date
The XD importer SHALL store the optional `Ngày nộp hồ sơ` field separately from receipt and fuel-payment dates, accept valid day/month/year, ISO and Excel serial dates, and expose it in preview, student list and Excel export.

#### Scenario: Excel text dates with hyphens
- **WHEN** an XLS or XLSX roster contains `10-07-2026`, `30-07-2026` or `03-08-2026` as submission date text
- **THEN** the importer SHALL interpret them as 10 July, 30 July and 3 August 2026 respectively, while rejecting impossible dates and mixed separators.

#### Scenario: Valid submission date
- **WHEN** a roster includes a valid submission date, with or without financial columns
- **THEN** it is stored as a calendar date and displayed as day/month/year without modifying settlement history.

#### Scenario: Empty or absent submission date
- **WHEN** a reimport has an empty or absent submission date
- **THEN** the existing submission date is retained.

#### Scenario: Invalid submission date or cached Excel error
- **WHEN** a submission date is impossible or has a cached formula error such as `#REF!`
- **THEN** preview reports the offending row and field and no student records are written.

#### Scenario: Distinct receipt date
- **WHEN** a roster contains only `Ngày nhận Hồ sơ`
- **THEN** the importer does not use it as submission date.
