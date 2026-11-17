# Spec Delta

## Purpose

Provide a clear, vehicle-specific repair history for training vehicles, including work progress, recorded costs, and the next maintenance targets.

## ADDED Requirements

### Requirement: Vehicle-specific navigation and history

The system SHALL provide a labeled "Nhật ký sửa xe" action for each vehicle to authorized viewers. The destination MUST identify the vehicle by plate, brand, and assigned teacher, and show only its records ordered by repair date and identifier descending. Navigation back to the vehicle list SHALL preserve list filters.

#### Scenario: Open a vehicle history
- **WHEN** a viewer opens the repair history for one vehicle
- **THEN** the page identifies that vehicle, excludes other vehicles' records, and offers a return link to the filtered vehicle list.

#### Scenario: Unknown vehicle
- **WHEN** a user requests history for a missing vehicle
- **THEN** the system displays an explicit error and does not create records.

### Requirement: Repair record lifecycle

Authorized editors SHALL create and update records containing repair date, work description, status, optional garage, odometer, cost, notes, next-maintenance date, and next-maintenance odometer. Status MUST be "Đang sửa" or "Hoàn tất". The vehicle association MUST remain fixed when editing. Only authorized deleters SHALL delete records after confirmation.

#### Scenario: Record and complete work
- **WHEN** an editor adds work as "Đang sửa" and later saves it as "Hoàn tất"
- **THEN** the saved record remains attached to the same vehicle and its updated status is visible in history.

#### Scenario: Record belongs to another vehicle
- **WHEN** a user attempts to edit or delete a record using a different vehicle identifier
- **THEN** the operation is rejected without modifying either vehicle's history.

### Requirement: Validated and recoverable entry

The system MUST require a real repair date, nonblank work description, and a supported status. Optional numeric values MUST be finite and nonnegative; costs MUST use VND precision. Maintenance date MUST not precede repair date, and target odometer MUST exceed recorded odometer when both exist. Invalid submissions SHALL retain entered values and show specific field errors without saving.

#### Scenario: Invalid fields
- **WHEN** a user submits negative cost, an invalid date, or a maintenance target below the recorded odometer
- **THEN** the form shows actionable errors, retains inputs, and writes no record.

#### Scenario: Optional cost is unknown
- **WHEN** an editor leaves optional cost or odometer blank
- **THEN** the record is saved with those values unknown rather than silently treating them as zero.

### Requirement: Honest summaries and maintenance reminders

The page SHALL summarize all records for the vehicle: total repairs, in-progress repairs, and total entered cost, with unknown costs distinguished. The latest dated record's maintenance targets SHALL define the next reminder, explicitly showing when none are set. Date reminders SHALL distinguish overdue, due today, within 30 days, and later. Odometer targets MUST not imply remaining distance without current vehicle mileage.

#### Scenario: Date-based reminder
- **WHEN** the latest repair has a maintenance date before today
- **THEN** the summary displays an overdue reminder with that date.

#### Scenario: Latest record has no reminder
- **WHEN** the latest dated repair has no maintenance targets
- **THEN** the summary says no next maintenance is scheduled instead of reviving an older reminder.

#### Scenario: Odometer-only target
- **WHEN** the latest repair provides only a maintenance odometer
- **THEN** the summary displays the target without claiming the vehicle has reached it.

### Requirement: Clear and accessible interaction

The page SHALL provide a primary "Thêm lần sửa chữa" action, labeled form sections, readable VND/date formatting, text-and-color status badges, pagination, and a purposeful empty state. Mobile layouts MUST keep vehicle identity, repair content, status, cost, and actions usable without a fixed-width form. Opening edit SHALL make the editing context and cancellation action clear.

#### Scenario: First record on a small screen
- **WHEN** an editor opens a vehicle without history on a narrow viewport
- **THEN** an empty state explains how to add the first repair and the labeled form remains usable.

### Requirement: Protected and persistent changes

All history operations MUST enforce existing vehicle view/edit/delete permissions on the server. Mutations MUST use POST and valid CSRF protection. Successful changes SHALL persist and generate audit entries identifying the vehicle and record. Failed writes MUST show an error, never a success message. Vehicles with repair history MUST be protected from deletion in single, bulk, and delete-all paths.

#### Scenario: Unauthorized mutation
- **WHEN** a view-only user submits a repair change or a mutation lacks a valid CSRF token
- **THEN** the change is denied and persisted history remains unchanged.

#### Scenario: Persistence failure
- **WHEN** saving or deleting a record fails
- **THEN** the system reports failure without claiming success.

#### Scenario: Vehicle deletion with repair history
- **WHEN** any supported deletion path targets a vehicle with repair history
- **THEN** the operation is rejected before deleting vehicles and tells the user to retain the vehicle using liquidation instead.

### Requirement: Teacher portal access to assigned vehicle history

The teacher portal SHALL list and expose repair histories only for vehicles whose assigned `gv_key` matches the authenticated teacher's nonempty key. A teacher SHALL view and add repair records for those vehicles and SHALL update only a record's status to "Đang sửa" or "Hoàn tất"; teachers MUST NOT edit other record fields or delete records. Every read and mutation MUST recheck vehicle assignment and record ownership on the server. All portal mutations MUST require the teacher CSRF token and a changed password.

#### Scenario: View assigned vehicles and history
- **WHEN** an authenticated teacher opens vehicle history
- **THEN** only vehicles currently assigned to that teacher are visible, with their repair records and status, and no delete action.

#### Scenario: Add repair to an assigned vehicle
- **WHEN** a teacher submits a valid repair record for a currently assigned vehicle
- **THEN** the record is saved for that vehicle and appears in its history.

#### Scenario: Update status only
- **WHEN** a teacher changes a repair record status for an assigned vehicle
- **THEN** only the status and update metadata change; all repair details remain unchanged.

#### Scenario: Unassigned vehicle, reassigned vehicle, or mismatched record
- **WHEN** a teacher submits a request for a vehicle not currently assigned to them, or a repair record that does not belong to that vehicle
- **THEN** the request is rejected without disclosing or modifying that vehicle's data.

#### Scenario: Delete attempt, missing CSRF, or unchanged default password
- **WHEN** a teacher attempts to delete a repair record, submits a mutation without the teacher CSRF token, or has not changed the default password
- **THEN** no repair data changes and the portal reports that the operation is unavailable.

### Requirement: Repair photo evidence

The teacher portal SHALL allow a teacher to attach up to 10 current-condition images from a phone or computer when adding a repair record. The server MUST validate actual image content, accept only JPEG, PNG, or WebP inputs up to 5 MB each and 12 megapixels, resize the longest edge to at most 1,600 pixels, re-encode as JPEG, strip source metadata, and keep each stored image at or below 1 MB. Images SHALL be associated with that repair record and stored in a private directory that denies direct HTTP access. An assigned teacher and authorized admins MUST view them only through access-checked image routes. Failed image validation or storage MUST be reported without saving a partial repair record or leaving orphaned images.

#### Scenario: Add photo evidence
- **WHEN** a teacher submits a repair record with valid photos
- **THEN** the images are optimized and attached to that vehicle's record, and an authorized admin can view them in its history.

#### Scenario: Invalid, excessive, or unoptimizable image
- **WHEN** a teacher submits an unsupported, oversized, invalid, or more-than-10 image selection
- **THEN** the portal reports the problem and stores neither a partial record nor partial image set.

#### Scenario: Image access by an unassigned teacher
- **WHEN** a teacher requests an image for a vehicle not currently assigned to their account
- **THEN** the image is not served.
