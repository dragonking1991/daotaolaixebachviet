# Design

## Context

As described in [proposal.md](proposal.md), training vehicles currently have general notes but no repair log. The existing module uses procedural PHP controllers, server-rendered AdminLTE/Bootstrap templates, MySQL tables initialized through `dt_ensure_tables()`, section permissions through `dt_p`/`dt_can`, and shared CSRF and audit helpers.

The [vehicle-repair-log spec](specs/vehicle-repair-log/spec.md) adds repair history, work status, and maintenance scheduling. Vehicle deletion includes single, selected, and delete-all paths, so preserving history requires considering all three.

## Goals / Non-Goals

**Goals:**

- Make it immediately clear which vehicle is being maintained and what work is in progress.
- Offer quick, recoverable entry without changing ordinary vehicle editing.
- Track actual entered costs without confusing missing values with zero.
- Make next-maintenance information understandable without claiming access to live mileage.
- Preserve permission boundaries and provide reliable, auditable persistence.

**Non-Goals:**

- Parts inventory, purchase orders, invoices, approval chains, or garage directories.
- Automatic notifications or changes to vehicle availability and liquidation status.
- Fuel-cost integration or generating maintenance intervals automatically.
- Fleet-wide repair dashboards, Excel imports, or exports in this change.

## Decisions

### Dedicated per-vehicle history page

Add a text-labeled "Nhật ký sửa xe" action in each vehicle row. Use a dedicated page rather than a modal: longer history, mobile layouts, validation recovery, and navigation work without complex JavaScript.

Page hierarchy:

1. Breadcrumb and return link retaining the vehicle-list filters.
2. Vehicle header with plate as the primary identity, brand, teacher, and liquidation badge.
3. Summary cards for total records, in-progress work, entered VND cost, and next maintenance.
4. Primary "Thêm lần sửa chữa" action leading to an inline form section when requested.
5. Paginated newest-first history showing date, work, garage, status, mileage, cost, maintenance targets, and permitted actions.

Use the existing design vocabulary with restrained primary colors, readable spacing, icons with text labels, responsive cards or stacked rows, and badges containing status text. Do not rely on color alone. Initial empty state offers the first-record action only to editors; viewers get explanatory text.

### Focused record form

Group inputs into "Thông tin sửa chữa", "Chi phí và đơn vị thực hiện", and "Lịch bảo dưỡng tiếp theo". Require repair date, description, and status; default a new record to today's date and "Đang sửa". Other fields are optional.

Show VND and km units beside labeled inputs. Blank cost means "Chưa nhập", not zero. Editing displays a clear heading and cancel link. Validation rerenders the same form with retained values and field errors. Successful submission redirects to history to prevent accidental resubmission.

### Additive vehicle-linked table

Create `dt_xe_suachua` using the repository table-prefix convention, with:

- Primary record ID and indexed `id_xe`.
- Repair date (`DATE`), work description, status (`dang_sua` or `hoan_tat`).
- Optional garage and notes with explicit length limits.
- Nullable odometer and maintenance odometer (`DECIMAL` km).
- Nullable cost (`DECIMAL`, integral VND precision).
- Nullable next-maintenance date (`DATE`).
- Created/updated timestamps and operator identity.

Index `(id_xe, ngay_sua, id)` for vehicle history ordering. Reference the stable vehicle ID rather than plate text so plate edits preserve history. Keep money decimal in persistence and validate bounded numeric input before conversion. Use nullable fields to distinguish unknown from zero.

Prefer a focused controller file and template under the existing training module over extending generic CRUD with special lifecycle rules. Register routes using existing vehicle permissions. Reuse shared date, audit, CSRF, formatting, and navigation helpers where their semantics fit.

### Explicit maintenance rule

The latest repair by repair date and ID defines the next maintenance targets. This simple rule is displayed in help text. A latest record without targets means "Chưa đặt lịch"; older targets remain visible in history but are not silently reused.

Date labels use the application's date/time convention: overdue, today, within the next 30 days, or scheduled later. Odometer-only targets show the absolute target and explain that current vehicle mileage is not available; no derived "km remaining" or odometer-based overdue badge is shown.

Alternative considered: a separate schedule entity with completion and recurrence. It would support independent schedules but adds workflows outside this request; defer it.

### Guarded mutation and history preservation

Every read verifies the vehicle exists. Every mutation checks both the vehicle and record association, validates POST/CSRF, and enforces the corresponding vehicle permission server-side. View permission does not imply edit or delete.

Check database write results and transaction completion when used; display explicit failures and never audit or redirect as success before a confirmed write. Audit includes vehicle ID and repair record ID. Escape all user-entered values when rendering.

Before any supported vehicle deletion, reject the operation if any targeted vehicle has history, with guidance to use liquidation instead. Bulk/delete-all checks happen before the first deletion so they cannot partially delete a batch. Liquidation keeps history readable and does not erase repair records.

Alternative considered: cascading history deletion. Reject it because routine vehicle removal could destroy financial and maintenance evidence.

### Teacher portal and image evidence

The portal's "Xe phụ trách" tab filters every vehicle query by the logged-in teacher's nonempty `gv_key`. Teachers can add repair/current-condition notes and change only a record's status; server-side ownership checks, teacher CSRF, and the changed-password gate apply on every mutation. No delete or general edit action is rendered or routed.

An add form accepts up to 10 JPEG/PNG/WebP images from mobile or desktop. The server verifies image bytes and dimensions, resizes the longest edge to 1,600 pixels, re-encodes as JPEG to remove source metadata, and caps each stored image at 1 MB. Files are stored with random names in `private_upload`, which denies direct HTTP access; both portal and admin image endpoints verify the associated repair, vehicle, and viewer authorization before streaming. Admin history renders the attached images as linked thumbnails. Upload and database failures clean up files created by the failed submission.

Alternative considered: storing original-resolution uploads. Reject it because phone photos can consume substantial persistent storage and retain unnecessary metadata.

## Risks / Trade-offs

- [Schema initialization differs between installed environments] → Follow the existing idempotent initializer and provide an explicit additive migration when required by deployment conventions; verify existing data is untouched.
- [Staff mistake unknown cost for no expense] → Show "Chưa nhập" and count records missing cost next to the entered-cost total.
- [Editing old records changes chronology] → Define latest by repair date and ID, not update time, and test the reminder rule after edits and deletes.
- [No live mileage feed] → Present odometer targets as targets only, without inferred due status.
- [Vehicle deletion protection misses an entry point] → Cover single, selected, and delete-all flows in regression tests.
- [Concurrent vehicle deletion and repair insertion] → Use the existing transaction facilities with parent-vehicle locking for insertion and protected deletion, or an equivalent database constraint compatible with deployed table engines.
- [Long history affects page usability] → Paginate records and calculate summaries across the entire vehicle history, not only the current page.

## Migration Plan

1. Add the new table through the established idempotent training schema path; no backfill or changes to existing vehicle rows.
2. Deploy controller, permission-guarded routes, template, deletion protections, and documentation together.
3. Verify empty history for existing vehicles, then create/edit/complete/delete a test repair and reload to confirm persistence.
4. Verify read-only access, invalid CSRF, invalid input recovery, and vehicle deletion protection.
5. Rollback application entry points if needed while retaining the additive table and history; do not drop user-entered repair data as part of rollback.
