# Sprint 4 Preview & Review — Driver Module, Customer Support, Testing & Diagram Sync (Weeks 10–11)

**Sprint Goal:** Add the Bus Driver Management & Schedule Portal (`US-08`) and Pilgrim Customer Support & Admin Inbox (`US-09`), prove every core function works reliably through automated & live browser testing, capture screenshot evidence, and audit all 6 diagrams against the finished PHP 8.2 + SQLite 3 (PDO) codebase.  
**Status:** Completed (**20 / 20 Functional Tests PASSED**)

---

## 1. Bus Driver Module, Customer Support & Bug Fixes Delivered
* **Admin Bus Drivers Page (`src/admin/drivers.php` & `src/classes/Driver.php`):** Add/remove drivers with automatic login accounts, assign drivers 1:1 to buses (`UNIQUE(driver_id)`), and toggle `Active` / `Out of Service` status for both drivers and buses.
* **Driver Read-Only Portal (`src/driver_portal.php`):** Displays driver profile, service status alerts, assigned bus & passenger count, bus schedule timeline, and passenger boarding checklist.
* **Pilgrim Customer Support & Admin Support Inbox (`src/support.php`, `src/admin/support.php`, `src/classes/SupportTicket.php`):** Added a direct **Contact Customer Support** button on the Pilgrim Portal leading to a structured support form linked to `SUPPORT_TICKETS` (`attendee_id` FK), delivering messages directly to the Admin Support Inbox with status tracking (`open` / `in_progress` / `resolved`) and official replies.
* **Cross-Page Consistency Fixes (`src/classes/Bus.php`):** Prevented assigning an `Out of Service` driver on `logistics.php` and enforced 1:1 driver-to-bus uniqueness across all pages.

---

## 2. Static & Automated Functional Verification
* **PHP Syntax Audit (`php -l`):** Verified all **30 PHP source files** in `src/` — **0 syntax errors**.
* **Automated End-to-End Test Suite (`src/tests/run_automated_tests.php`):** Executed all **20 test cases (`TC-01` through `TC-20`)** via real HTTP requests with cookie sessions, CSRF tokens, redirects, and DB assertions against `http://127.0.0.1:8088` — **20/20 PASSED**.
* **9 Live Screenshots Saved:** Stored in `docs/testing/screenshots/` (`01_login_invalid_alert.png` through `09_admin_support_inbox.png`).

---

## 3. Diagram-to-Code Consistency Audit (100% Synced to PHP 8.2 + SQLite 3 PDO)

| Diagram | Audit Finding vs. Final Code | Status |
|---|---|---|
| **Wireframes (`01-wireframes.html`)** | Covers all 11 implemented screens across Admin, Driver, and Attendee roles (including Bus Drivers, Customer Support Form, and Admin Support Inbox). | Verified Consistent |
| **Screen Flow (`02-screen-flow.md`)** | Matches all routes (`/portal.php`, `/support.php`, `/driver_portal.php`, `/admin/*`) and 3-role RBAC guards. | Verified Consistent |
| **ERD (`03-erd.md`)** | All 7 normalized tables (`USERS`, `DRIVERS`, `BUSES`, `TENTS`, `ATTENDEES`, `ITINERARIES`, `SUPPORT_TICKETS`), columns, PKs, FKs, `UNIQUE(driver_id)`, and `CHECK` constraints match `sql/schema.sql` 100%. | Verified Consistent |
| **DFD (`04-dfd.md`)** | Level 0 & Level 1 processes (`1.0`–`8.0`) and data stores (`D1`–`D7`) accurately represent implemented data flows. | Verified Consistent |
| **Class Diagram (`05-class-diagram.md`)** | All 9 PHP classes (`Database`, `User`, `Driver`, `Attendee`, `Bus`, `Tent`, `Itinerary`, `SupportTicket`, `ReportGenerator`) match exact method signatures. | Verified Consistent |
| **Deployment Diagram (`06-deployment-diagram.md`)** | Accurately reflects Client Browsers ↔ HTTP ↔ XAMPP Apache / PHP 8.2.12 + PDO `pdo_sqlite` ↔ Embedded SQLite 3 (`data/hajj_umrah.sqlite`). | Verified Consistent |

---

## Sprint 4 Exit Gate Check
- [x] All 20 formal functional test cases executed and documented (`docs/testing/test-cases-and-results.md`)
- [x] 9 live visual testing screenshots captured and saved (`docs/testing/screenshots/`)
- [x] All 6 SE diagrams audited and synced 100% with the final PHP + SQLite 3 codebase
- [x] Final Project Report and Presentation & Live Demo Playbook updated and ready for submission
