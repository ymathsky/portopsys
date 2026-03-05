# Changelog

## [1.1.0] - 2026-03-01

### Bug Fixes
- **Capacity bar always showing 0%** — both `customer/index.php` and `api/schedule-status.php` were reading a non-existent key `booked_count`; corrected to `passengers_booked` which matches the actual database column alias returned by `getTodaySchedules()`.
- **Corrupted emoji icons on landing page** — Advance Reservation (`📋`) and Trip Schedules (`🗓️`) action card icons in `index.php` were stored as corrupted bytes and rendered as question marks; fixed via UTF-8 safe file rewrite.

### New Features
- **Vessel-specific service filtering** — on the Get Queue Token flow (Step 2), available services are now filtered to only show services assigned to the selected vessel. Clicking a different vessel re-fetches services via `api/vessel-services.php`. Includes a loading spinner and a "No services available" fallback message.
- **Capacity remaining on schedule cards** — each schedule card in the token flow now displays a color-coded seats-remaining label:
  - 🔴 "Only X seat(s) left!" when ≤ 10 seats remain
  - 🟠 "X seats left" when ≤ 30 seats remain
  - 🟢 "X seats available" when plenty remain
  - Live polling (every 30 s) via `api/schedule-status.php` keeps the label and progress bar up to date. `cap_remaining` added to API response.
- **PIN error animation & notification** — on the Schedules admin page, entering a wrong PIN now triggers a shake animation on the confirmation modal card, highlights the input with a red border, and shows a slide-in toast notification ("Incorrect PIN"). Correct PIN shows a green success toast before reloading. The form submission was converted from a full page reload to an AJAX fetch request.

### Improvements
- **App name rename** — "Port Queue Management System" renamed to **"Port Queuing Management System"** across all files: `config/config.php`, `index.php`, `customer/index.php`, `admin/manifest.php`, `admin/schedules.php`, `.htaccess`, `README.md`, `USER_MANUAL.md`, `setup_alabat_port.sql`.

### New Files
- `api/vessel-services.php` — returns the list of service IDs assigned to a given vessel (`?vessel_id=X`), used by the token flow to filter service buttons.

### Files Modified
| File | Change |
|------|--------|
| `config/config.php` | App name constant updated |
| `index.php` | Emoji fix, app name rename |
| `customer/index.php` | Vessel-service filtering, capacity bar fix, remaining seats label |
| `admin/schedules.php` | PIN modal — AJAX, shake animation, toast notification |
| `admin/manifest.php` | App name rename |
| `api/schedule-status.php` | Fixed `passengers_booked` key, added `cap_remaining` to response |
| `api/vessel-services.php` | **New file** |
| `.htaccess` | App name rename |
| `README.md` | App name rename |
| `USER_MANUAL.md` | App name rename |
| `setup_alabat_port.sql` | App name rename |
