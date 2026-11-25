# Sprint 4 Preview & Review — Driver Module, Testing & Diagram Sync (Weeks 10–11)

**Sprint Goal:** Add the Bus Driver Management & Schedule Portal (`US-08`), prove every core function works reliably through automated & live browser testing, capture screenshot evidence, and audit all 6 diagrams against the finished PHP 8.2 + SQLite 3 (PDO) codebase.  
**Status:** Completed (**17 / 17 Functional Tests PASSED**)

---

## 1. Bus Driver Module & Bug Fixes Delivered
* **Admin Bus Drivers Page (`src/admin/drivers.php` & `src/classes/Driver.php`):** Add/remove drivers with automatic login accounts, assign drivers 1:1 to buses (`UNIQUE(driver_id)`), and toggle `Active` / `Out of Service` status for both drivers and buses.
* **Driver Read-Only Portal (`src/driver_portal.php`):** Displays driver profile, service status alerts, assigned bus & passenger count, bus schedule timeline, and passenger boarding checklist.
* **Cross-Page Consistency Fixes (`src/classes/Bus.php`):** Prevented assigning an `Out of Service` driver on `logistics.php` and enforced 1:1 driver-to-bus uniqueness across all pages.

---

## 2. Static & Automated Functional Verification
* **PHP Syntax Audit (`php -l`):** Verified all **25 PHP source files** in `src/` — **0 syntax errors**.
* **Automated End-to-End Test Suite (`src/tests/run_automated_tests.php`):** Executed all **17 test cases (`TC-01` through `TC-17`)** via real HTTP requests with cookie sessions, CSRF tokens, redirects, and DB assertions against `http://127.0.0.1:8088` — **17/17 PASSED**.
* **7 Live Screenshots Saved:** Stored in `docs/testing/screenshots/` (`01_login_invalid_alert.png` through `07_driver_portal.png`).

---

## 3. Diagram-to-Code Consistency Audit (100% Synced to PHP 8.2 + SQLite 3 PDO)

| Diagram | Audit Finding vs. Final Code | Status |
|---|---|---|
| **Wireframes (`01-wireframes.html`)** | Covers all implemented screens across Admin, Driver, and Attendee roles (including Bus Drivers, Logistics, and Driver Portal). | Verified Consistent |
| **Screen Flow (`02-screen-flow.md`)** | Matches all routes and 3-role RBAC guards (`admin`, `driver`, `attendee`). | Verified Consistent |
| **ERD (`03-erd.md`)** | All 6 normalized tables (`USERS`, `DRIVERS`, `BUSES`, `TENTS`, `ATTENDEES`, `ITINERARIES`), columns, PKs, FKs, `UNIQUE(driver_id)`, and `CHECK` constraints match `sql/schema.sql` 100%. | Verified Consistent |
| **DFD (`04-dfd.md`)** | Level 0 & Level 1 processes (`1.0`–`7.0`) and data stores (`D1`–`D6`) accurately represent implemented data flows. | Verified Consistent |
| **Class Diagram (`05-class-diagram.md`)** | All 8 PHP classes (`Database`, `User`, `Driver`, `Attendee`, `Bus`, `Tent`, `Itinerary`, `ReportGenerator`) match exact method signatures. | Verified Consistent |
| **Deployment Diagram (`06-deployment-diagram.md`)** | Accurately reflects Client Browsers ↔ HTTP ↔ XAMPP Apache / PHP 8.2.12 + PDO `pdo_sqlite` ↔ Embedded SQLite 3 (`data/hajj_umrah.sqlite`). | Verified Consistent |

---

## Sprint 4 Exit Gate Check
- [x] All 17 formal functional test cases executed and documented (`docs/testing/test-cases-and-results.md`)
- [x] 7 live visual testing screenshots captured and saved (`docs/testing/screenshots/`)
- [x] All 6 SE diagrams audited and synced 100% with the final PHP + SQLite 3 codebase
- [x] Final Project Report and Presentation & Live Demo Playbook updated and ready for submission
