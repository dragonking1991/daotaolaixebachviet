# Tasks

## 1. Persistence and vehicle lifecycle

- [x] 1.1 Add the idempotent vehicle repair table with vehicle/history index, nullable optional amounts and maintenance targets, timestamps, and operator identity; verify initialization twice leaves existing vehicles unchanged.
- [x] 1.2 Implement vehicle-scoped history queries, full-history summary totals, pagination, and latest-dated-record maintenance selection; test isolation between two vehicles and summaries spanning multiple pages.
- [x] 1.3 Protect single, selected, and delete-all vehicle deletion against existing repair history before any deletion; handle concurrent insertion/deletion safely and verify liquidation still preserves readable history.

## 2. Validated record operations

- [x] 2.1 Add read/create/update/delete routes with existing vehicle permissions, POST-only mutations, CSRF checks, and vehicle-record association checks; verify view-only, missing-token, missing-vehicle, and cross-vehicle requests are rejected appropriately.
- [x] 2.2 Implement bounded input validation for real dates, required description, supported status, finite nonnegative numbers, integral VND cost, optional unknown values, and maintenance target ordering; add focused valid/invalid boundary cases and verify failed submissions preserve input.
- [x] 2.3 Implement checked persistence, explicit database errors, successful-submit redirects, and audit records containing vehicle/repair identifiers; verify create/edit/complete/delete survive reload and simulated write failures never report success.

## 3. Vehicle history UX

- [x] 3.1 Add the labeled "Nhật ký sửa xe" row action and dedicated responsive page with vehicle identity, preserved list return context, all-history summaries, readable history, permission-aware actions, pagination, and empty states; verify existing vehicle actions and list filters remain unchanged.
- [x] 3.2 Build the labeled add/edit form with grouped repair, cost/garage, and maintenance sections, units, retained input, field errors, clear edit context, and cancellation; verify keyboard use and narrow-screen layout without fixed-width form overflow.
- [x] 3.3 Render text-and-color work statuses, unknown-cost indicators, and maintenance labels for overdue/today/within-30-days/later/no-schedule; verify exact date boundaries, latest-record edits/deletes, and odometer-only targets without false remaining-distance claims.

## 4. Documentation and integrated verification

- [x] 4.1 Update the existing vehicle administration documentation with entry workflow, permissions, optional costs, reminder selection, and deletion/liquidation behavior; verify instructions match implemented labels and actions.
- [x] 4.2 Run PHP syntax validation for changed application files and the focused regression tests covering the capability; verify no unrelated behavior changes and record any environment limitations explicitly.
- [ ] 4.3 Exercise the rendered interface using the existing local stack/browser where available: empty history, first entry, update/complete, delete confirmation, read-only view, mobile layout, and vehicle-list return; verify persisted results and resolve defects before marking implementation complete.

## 5. Teacher portal access

- [x] 5.1 Share the idempotent repair schema and input validation with the teacher portal; verify portal-first access works without an admin visit and validation rules remain consistent.
- [x] 5.2 Add an assigned-vehicle list and responsive per-vehicle repair history with add form; scope every query to the authenticated nonempty teacher key and verify another teacher cannot see the data.
- [x] 5.3 Add POST-only, CSRF-protected status updates that change no other fields; forbid deletion and enforce changed-password gate, verify reassignment and cross-record attempts are rejected, and add focused regression cases.
- [ ] 5.4 Update teacher portal documentation and test the vehicle/history forms and status workflow at desktop and mobile widths when the app is available.
- [ ] 5.5 Allow up to 10 validated condition photos per teacher-created repair, resize/re-encode them with a 1 MB stored-file ceiling, display them in admin history, and test upload failures and cleanup.
