# Functional Testing Evidence & Results (Sprint 4)

**Test Date:** 2026-11-25  
**Test Environment:** PHP 8.2.12 + SQLite 3 PDO (`C:\xampp\php\php.exe -S 127.0.0.1:8088 -t src`), Automated HTTP Suite (`src/tests/run_automated_tests.php`), and Live Chromium Browser Inspection  
**Overall Result:** **17 / 17 PASSED (100%)** — Zero PHP syntax warnings across all 25 PHP files; zero UI layout or clipping defects.

---

## 1. Completed Functional Test Case Table (TC-01 to TC-17)

| Test ID | Feature | Input / Action | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| **TC-01** | Login (Valid Admin) | Submit `admin` / `Admin@2026` on `/login.php` | Redirects to Admin Dashboard with active session | HTTP 302 redirect to `/admin/dashboard.php`; dashboard rendered with 4 KPI cards & occupancy bars (`02_admin_dashboard.png`) | **PASS** |
| **TC-02** | Login (Invalid Password) | Submit `admin` / `WrongPassword123` | Blocked with `"Invalid credentials"` error alert | HTTP 200 staying on `/login.php`; red alert banner displayed (`01_login_invalid_alert.png`) | **PASS** |
| **TC-03** | RBAC Access Control | Logged-in Attendee (`1098765437`) visits `/admin/attendees.php` | Access denied / redirected back to Attendee Portal | HTTP 302 redirect to `/portal.php` with access-denied flash alert | **PASS** |
| **TC-04** | Add Attendee + Auto Account | Submit valid attendee (`Salem Nasser Al-Harthi`, ID `1099001122`, `BUS-102`, `TENT-B02`) | Saved in `ATTENDEES` & `USERS`, shown in directory | Attendee `#13` created atomically with linked portal login and verified in directory | **PASS** |
| **TC-05** | Bus Capacity Enforcement | Assign attendee to `BUS-101` (`5/5` FULL) on `/admin/allocate.php` | Blocked with `"Bus at max capacity"` alert | Blocked with red alert `"Bus at max capacity: BUS-101 already has 5/5 seats occupied."` (`03_bus_full_capacity_alert.png`) | **PASS** |
| **TC-06** | Search by National ID | Query `/admin/attendees.php?q=1098765431` | Displays matching attendee only | Returned exclusively `Abdullah Nasser Al-Qahtani` (`1098765431`), filtering out other 11 rows | **PASS** |
| **TC-07** | Attendee Portal Live Sync | Admin reassigns test attendee to `BUS-201` / `TENT-U01`; attendee logs into `/portal.php` | Displays updated bus, tent, and bus itinerary immediately | Attendee logged in with National ID and immediately saw `BUS-201`, `TENT-U01`, and `Umrah Shuttle to Masjid Al-Haram` | **PASS** |
| **TC-08** | Reports (Manifest & CSV) | Open `/admin/reports.php?type=bus&id=1` and `&export=csv` | Lists all 5 passengers on `BUS-101` in printable HTML and valid CSV | Printable manifest rendered all 5 passengers with boarding boxes (`04_printable_bus_manifest.png`); CSV returned `text/csv` | **PASS** |
| **TC-09** | Edit Attendee Record | Update test attendee name & phone on `/admin/edit_attendee.php` | Changes persisted in database and reflected in list | Name updated to `Salem Nasser Al-Harthi (Updated)` and phone to `0551112233` | **PASS** |
| **TC-10** | Delete Attendee + Cascade | POST delete with CSRF token on `/admin/delete_attendee.php` | Record deleted from `ATTENDEES` and cascaded from `USERS` | Confirmation flash shown; search confirmed 0 matching records remain | **PASS** |
| **TC-11** | Tent Capacity Enforcement | Assign attendee to `TENT-A01` (`6/6` FULL) on `/admin/allocate.php` | Blocked with `"Tent at max capacity"` alert | Blocked with `"Tent at max capacity: TENT-A01 already has 6/6 spaces occupied."` | **PASS** |
| **TC-12** | Logout & Session Protection | Visit `/logout.php`, then attempt `/admin/dashboard.php` | Session destroyed; protected route redirects to `/login.php` | HTTP 302 to `/login.php` on logout and HTTP 302 to `/login.php` on subsequent protected request | **PASS** |
| **TC-13** | Add Driver & Assign Bus | Admin adds `Omar Al-Zamil` (`driver4`, `DL-2026-404`) and assigns to `BUS-201` on `/admin/drivers.php` | Saved in `USERS` (`role=driver`) & `DRIVERS`, linked to `BUS-201` | Driver `#4` created atomically and assigned to `BUS-201` (`06_admin_drivers.png`) | **PASS** |
| **TC-14** | Driver Portal & RBAC | Driver `driver4` logs in; visits `/driver_portal.php` and attempts `/admin/drivers.php` | Shows `BUS-201` + bus schedule; blocks `/admin/*` | Redirected to `/driver_portal.php` with bus & schedule (`07_driver_portal.png`); `/admin/drivers.php` blocked | **PASS** |
| **TC-15** | Out of Service Enforcement | Admin toggles `BUS-201` to `out_of_service`; check Driver Portal & try allocating an attendee | Driver alerted; passenger allocation rejected | Driver Portal showed `OUT OF SERVICE` alert; allocation blocked with `"Bus out of service"`; restored cleanly | **PASS** |
| **TC-16** | Driver Status Toggle & Removal | Admin toggles `driver4` to `out_of_service`, then removes `driver4` | Status updated to `OUT OF SERVICE`, then removed cleanly (`ON DELETE CASCADE`/`SET NULL`) | Status badge verified; driver `#4` removed & `BUS-201` restored | **PASS** |
| **TC-17** | Logistics OOS & Unique Driver Guard | On `/admin/logistics.php`, try assigning an `Out of Service` driver to `BUS-201` | Rejected with error; enforces 1:0..1 `UNIQUE(driver_id)` | Blocked with `"Out of Service and cannot be assigned to a bus"` alert | **PASS** |

---

## 2. Captured Live Visual Evidence (7 Screenshots)

All screenshots were captured live from the running PHP 8.2 + SQLite 3 application and stored in `docs/testing/screenshots/`:

1. **`01_login_invalid_alert.png`** — TC-02 invalid login rejection alert on `/login.php`
2. **`02_admin_dashboard.png`** — TC-01 Admin Operations Dashboard showing KPIs, Bus Fleet Occupancy (`BUS-101` FULL), and Tent Occupancy (`TENT-A01` FULL)
3. **`03_bus_full_capacity_alert.png`** — TC-05 server-side overbooking prevention alert on `/admin/allocate.php`
4. **`04_printable_bus_manifest.png`** — TC-08 official printable `BUS-101` Passenger List with ground-staff boarding check columns
5. **`05_attendee_portal.png`** — TC-07 Attendee Read-Only Portal (`Abdullah Nasser Al-Qahtani`) showing assigned bus, driver contact, Mina tent zone, and live itinerary
6. **`06_admin_drivers.png`** — TC-13 Admin Bus Drivers Management (`/admin/drivers.php`) showing driver assignments and Bus Fleet Service Status controls
7. **`07_driver_portal.png`** — TC-14 Bus Driver Schedule Portal (`/driver_portal.php` as `driver1`) showing `BUS-101`, itinerary schedule, and 5-pilgrim boarding list

---

## 3. Reproducing the Automated Test Suite
```powershell
# Terminal 1: Start local PHP server
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8088 -t 'cpit251-hajj-umrah\src'

# Terminal 2: Execute all 17 functional test cases
& 'C:\xampp\php\php.exe' 'cpit251-hajj-umrah\src\tests\run_automated_tests.php' 'http://127.0.0.1:8088'
```
