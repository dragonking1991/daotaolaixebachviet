## Why

The training vehicle list has no dedicated repair history. Staff need an understandable, vehicle-specific record of work, costs, repair progress, and upcoming maintenance without mixing these records with general vehicle notes.

## What Changes

- Add a clearly labeled "Nhật ký sửa xe" entry point on each training vehicle.
- Provide a dedicated vehicle history page with vehicle identity, summary cards, newest-first records, and an accessible add/edit form.
- Record repair date, work performed, garage, odometer, cost, notes, and repair status (in progress or completed).
- Allow assigned teachers to attach up to 10 optimized condition photos to new repair records and let admins view them in the history.
- Track optional next-maintenance date and odometer targets, with clear upcoming/overdue labels.
- Support creating, updating, and deleting records using existing vehicle permissions, CSRF protection, validation, and audit conventions.
- Persist records per vehicle using an additive database change; preserve existing vehicle management behavior.

## Capabilities

### New Capabilities

- `vehicle-repair-log`: Manage repair history, repair progress, costs, and next-maintenance targets for individual training vehicles.

### Modified Capabilities

None.

## Impact

- Training vehicle controller, routing, list template, and a dedicated repair-history template.
- Training schema initialization and vehicle deletion handling to prevent orphaned history or accidental loss.
- Existing vehicle view/edit/delete permissions and admin CSRF/audit helpers.
- Focused regression tests and vehicle-management documentation.
- No automatic messages, inventory, invoice management, fuel-cost synchronization, or changes to training eligibility calculations.
