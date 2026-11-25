# CPIT251 — Final Project Report

**Faculty of Computing & Information Technology (FCIT)**  
**King Abdulaziz University (KAU)**  

---

# Moyassir (ميسر) — Hajj and Umrah Registration System
### Centralized Web-Based Campaign Logistics, Driver & Pilgrim Portal (PHP 8.2 + SQLite 3 PDO)

| Field | Details |
|---|---|
| **Course** | CPIT251 — Software Engineering |
| **Instructor** | Prof. Mohammed J. Alhaddad |
| **Section** | IT2 |
| **Team Members** | 1. Mohammed Aljedani (`2435544`)<br>2. Omar Alofi (`2436990`)<br>3. Saud Faisal Almesfer (`2435484`) |

---

## Table of Contents
1. [Chapter 1: Introduction & Problem Statement](#chapter-1-introduction--problem-statement)
2. [Chapter 2: Methodology (Agile Scrum)](#chapter-2-methodology-agile-scrum)
3. [Chapter 3: System Requirements](#chapter-3-system-requirements)
4. [Chapter 4: System Design & Software Engineering Diagrams](#chapter-4-system-design--software-engineering-diagrams)
5. [Chapter 5: Implementation](#chapter-5-implementation)
6. [Chapter 6: Testing & Validation](#chapter-6-testing--validation)
7. [Chapter 7: Conclusion & Future Work](#chapter-7-conclusion--future-work)
8. [References](#references)
9. [Appendices](#appendices)

---

## Chapter 1: Introduction & Problem Statement

### 1.1 Project Introduction & Scope
**Moyassir (ميسر)** is a centralized, responsive web application for **Hajj and Umrah Registration & Campaign Logistics**, built with **PHP 8.2** and an embedded relational **SQLite 3 database (via PHP PDO)**. Its primary objective is to transition local Hajj and Umrah campaign operators away from fragmented, error-prone manual workflows—such as paper lists, standalone Excel sheets, and scattered WhatsApp broadcasts—to a reliable relational database acting as a single source of truth.

* **Intended Users (3 Role-Separated Portals):**
  * **Campaign Managers (Administrators):** Manage attendee registrations, add/remove bus drivers, assign drivers 1:1 to buses, toggle `Active` / `Out of Service` status, assign buses and tents with automated capacity protection, update campaign schedules/itineraries, search and filter records, and generate printable ground-staff manifests and CSV rosters.
  * **Bus Drivers:** Sign into a dedicated read-only Driver Portal to view their service status, assigned bus & passenger count, departure time, bus itinerary schedule, and passenger boarding checklist.
  * **Attendees (Pilgrims):** Sign into a simple, mobile-friendly read-only portal using their National ID to view their personal pass, assigned bus & driver contact, tent location zone, and live itinerary.
* **Scope Note (Hajj & Umrah Alignment):** The system supports both **Hajj** and **Umrah** campaigns via `ATTENDEES.campaign_type` (`'Hajj'` | `'Umrah'`), handling both Hajj camp tents (`Mina Zone A/B`) and Umrah housing blocks & Haram shuttles (`Aziziyah Residence`).

### 1.2 Problem Statement
Coordinating hundreds or thousands of pilgrims and drivers across buses, tents, and strict multi-day ritual timeframes creates major logistical challenges when managed manually:
1. **Attendee & Driver Confusion:** Pilgrims frequently misplace their assigned bus number or Mina tent zone, while drivers lack live visibility into updated schedules and passenger lists.
2. **Inefficient Updates:** Managers struggle to broadcast last-minute departure time changes, tent reassignments, or out-of-service vehicle replacements reliably across noisy WhatsApp groups.
3. **Data Loss, Duplication & Overbooking:** Manual spreadsheet edits frequently cause duplicate entries, driver double-booking, and accidental bus or tent overbooking.

### 1.3 Supporting Evidence & Justification

#### A. Existing System Analysis — The Nusuk Platform
The official **Nusuk** platform (`https://www.nusuk.sa`), supervised by the Ministry of Hajj and Umrah, manages national-level eligibility verification (via *Nafath*), domestic package payments (via *SADAD*), and official permit issuance. However, Nusuk does **not** manage internal campaign-level bus seat manifests, private bus driver dispatch, specific internal tent numbers, or real-time ground-staff boarding checklists. Our system complements Nusuk by handling these internal campaign logistics (full comparison table in `docs/evidence/nusuk-analysis.md`).

#### B. Official Open Data — GASTAT & Ministry of Hajj and Umrah
Official statistics published by the **General Authority for Statistics (GASTAT)** underscore the operational scale requiring structured database management:

| Metric | Hajj 1445 AH (2024) | Hajj 1446 AH (2025) |
|---|---:|---:|
| **Total Pilgrims** | 1,833,164 | 1,673,230 |
| **External Pilgrims** | 1,611,310 | 1,506,576 |
| **Domestic (Internal) Pilgrims** | 221,854 | 166,654 |

Even a single licensed domestic campaign serves 300 to 2,000+ pilgrims within a tightly regulated 5-day movement schedule, justifying a normalized relational database with strict foreign-key and capacity constraints.

#### C. Academic Literature & Operational Evidence
Peer-reviewed Hajj transportation research by **Owaidah et al. (2023)** (*PLOS ONE*) demonstrates through discrete-event simulation across Makkah, Mina, Arafat, and Muzdalifah that pilgrim transport operates in strict camp-bound cohorts where boarding delays, vehicle breakdowns, or capacity mismatches cause cascading congestion across subsequent ritual phases. Replacing manual spreadsheets—which lack referential integrity and concurrency control (Sommerville, 2016)—with **Moyassir's** normalized 3NF database directly enforces seat/tent capacity limits, 1:1 driver-to-bus dispatch, and real-time schedule synchronization. *(Structured stakeholder interview and pilgrim survey instruments are also provided in Appendices A and B for future field evaluation.)*

---

## Chapter 2: Methodology (Agile Scrum)

We adopted the **Agile (Scrum)** software development methodology, organizing delivery into five iterative sprints (Sprint 0 through Sprint 4). Agile suited this project because it allowed early validation of our 3NF relational schema and core CRUD rules, and accommodated iterative refinements—including **transitioning from heavy standalone Oracle XE setup to portable relational SQLite 3 via PHP PDO** for frictionless multi-laptop collaboration, and **adding a dedicated Bus Driver module (`US-08`)** in response to operational review.

### 2.1 Team Scrum Roles
* **Mohammed Aljedani (2435544) — Product Owner & Database Lead:** Managed the Product Backlog, authored the 6-table 3NF SQL DDL & seed scripts (`sql/schema.sql`, `sql/seed.sql`), and led ERD/DFD modeling.
* **Omar Alofi (2436990) — Scrum Master & Backend Lead:** Facilitated sprint reviews, implemented the 8 PHP 8.2 OOP classes (`Database`, `User`, `Driver`, `Attendee`, `Bus`, `Tent`, `Itinerary`, `ReportGenerator`), RBAC authentication, and capacity/status validation.
* **Saud Faisal Almesfer (2435484) — Frontend & QA Lead:** Designed UI wireframes, responsive CSS, Attendee & Driver portals, printable manifest layouts, and executed the 17-case automated test suite & screenshot verification.

### 2.2 Product Backlog (8 Core Functions)

| ID | Role | User Story | Core Function | Priority | Sprint |
|---|---|---|---|---|---|
| **US-01** | Admin / Driver / Attendee | Log in securely and access only role-permitted screens. | Login & RBAC Auth | High | Sprint 2 |
| **US-02** | Admin | Add, view, edit, and delete attendee records with auto-created portal accounts. | Attendee CRUD | High | Sprint 2 |
| **US-03** | Admin | Assign attendees to buses and tents with server-side capacity & status checks. | Bus & Tent Allocation | High | Sprint 2 |
| **US-04** | Admin | Search and filter attendees by National ID, name, phone, bus, tent, or campaign type. | Search & Filtering | Medium | Sprint 3 |
| **US-05** | Attendee | View personal bus number, driver contact, tent zone, and itinerary (read-only). | Attendee Portal | High | Sprint 3 |
| **US-06** | Admin | Generate printable bus manifests and tent rosters (PDF/Print & CSV). | Report Generation | Medium | Sprint 3 |
| **US-07** | Admin | Add/update buses, departure times, tents, and itineraries reflected live on portals. | Logistics Management | Medium | Sprint 3 |
| **US-08** | Admin / Driver | Add/remove bus drivers, assign drivers 1:1 to buses, toggle Active / Out of Service status, and serve a Driver Schedule Portal. | Driver Management & Portal | High | Sprint 4 |

### 2.3 Sprint Execution Summary

| Sprint | Weeks | Sprint Goal & Delivered Artifacts | Status |
|---|---|---|---|
| **Sprint 0** | Weeks 1–3 | Environment setup (XAMPP PHP 8.2 + PDO SQLite), repo structure (`/docs`, `/sql`, `/src`), Scrum roles, backlog, and supporting evidence. | Completed |
| **Sprint 1** | Weeks 4–5 | All 6 SE diagrams (Wireframes, Screen Flow, ERD, DFD L0/L1, Class, Deployment) + normalized `sql/schema.sql` & `sql/seed.sql`. | Completed |
| **Sprint 2** | Weeks 6–7 | `Database` PDO class, bcrypt auth & CSRF protection, Admin Dashboard, Attendee CRUD + auto account provisioning, capacity-checked Allocation. | Completed |
| **Sprint 3** | Weeks 8–9 | Mobile-first Attendee Portal, multi-criteria Search/Filter, Printable Manifests + CSV Export, Bus/Tent/Itinerary Management, UI polish. | Completed |
| **Sprint 4** | Weeks 10–11 | Bus Driver Management & Driver Portal (`US-08`), PHP lint (25/25 clean), **17/17 automated tests PASSED**, 7 live UI screenshots, and 100% diagram-to-code sync. | Completed |

---

## Chapter 3: System Requirements

### 3.1 Functional Requirements (8 Core Functions — Exceeding Minimum 4)
1. **FR-01 Three-Role Authentication & RBAC:** Authenticate Admins, Drivers, and Attendees against bcrypt-hashed passwords in `USERS`, maintain PHP sessions, and block cross-role URL access.
2. **FR-02 Attendee Lifecycle CRUD:** Create (with atomic `USERS` account creation), view, edit (syncing National ID changes and optional password resets), and cascade-delete attendee records.
3. **FR-03 Capacity & Service-Status Protected Allocation:** Validate live `COUNT(*)` occupancy against `max_capacity` and verify `BUSES.status = 'active'` before saving assignments, exempting the attendee's own current seat to prevent self-blocking.
4. **FR-04 Multi-Criteria Search & Filtering:** Search attendees by full name, National ID, or phone using bound SQL `LIKE` predicates combined with Bus, Tent, and Campaign Type dropdown filters.
5. **FR-05 Read-Only Attendee Portal:** Render a personalized, mobile-friendly view of the logged-in attendee's bus, driver phone, departure time, service status alerts, tent zone, and itinerary milestones.
6. **FR-06 Manifest & Roster Reporting:** Generate printable HTML/PDF bus passenger manifests and tent occupancy rosters with ground-staff check-in boxes and one-click CSV download.
7. **FR-07 Logistics & Itinerary Updates:** Allow Admins to create or update buses (driver, status, capacity, departure time), tents, and itinerary schedules that propagate immediately to driver and attendee portals.
8. **FR-08 Bus Driver Management & Driver Portal:** Allow Admins to add/remove drivers (with auto-created `driver` login accounts), assign drivers 1:1 to buses (`UNIQUE(driver_id)`), and toggle `Active` / `Out of Service` status; allow Drivers to view their assigned bus, schedule timeline, and passenger manifest on `/driver_portal.php`.

### 3.2 Non-Functional Requirements
* **Security:** PDO prepared statements with named bound parameters (`PDOStatement::bindValue`) on 100% of SQL queries to prevent SQL injection; `PASSWORD_BCRYPT` hashing; CSRF tokens on all POST forms; HTML escaping (`htmlspecialchars`) against XSS; session ID regeneration on login.
* **Usability & Responsiveness:** Responsive CSS layout supporting desktop Admin workstations and mobile Driver/Attendee smartphones; dedicated `@media print` stylesheet for paper/PDF manifests.
* **Data Integrity & Portability:** 3NF relational SQLite 3 schema with `PRAGMA foreign_keys = ON`, Primary Keys, Foreign Keys (`ON DELETE CASCADE` / `ON DELETE SET NULL`), `UNIQUE` constraints, `CHECK` constraints, and ACID transactions—auto-initializing seamlessly on any team machine.

---

## Chapter 4: System Design & Software Engineering Diagrams

> **Diagram Export Note:** All diagrams can be viewed and exported as crisp PDF/PNG images by opening `docs/diagrams/diagrams-viewer.html` and `docs/diagrams/01-wireframes.html` in Chrome or Edge. Every diagram below matches the final implemented PHP 8.2 + SQLite 3 codebase 100%.

### 4.1 User Interface & Design
1. **Wireframes / UI Mockups:** Full 9-screen wireframe blueprint located in `docs/diagrams/01-wireframes.html` (Login, Admin Dashboard, Attendees CRUD/Search, Add/Edit Attendee, Bus & Tent Allocation, Printable Manifest, Bus Drivers & Service Status, Logistics & Itineraries, Mobile Attendee Portal, and Mobile Driver Portal).
2. **Screen Flow Diagram:** Complete 3-role guarded flow diagram and access matrix located in `docs/diagrams/02-screen-flow.md`.

### 4.2 Database & Data Design
1. **Entity-Relationship Diagram (ERD):** 6 normalized tables (`USERS`, `DRIVERS`, `BUSES`, `TENTS`, `ATTENDEES`, `ITINERARIES`) with cardinality rules (`1:0..1` driver-to-bus uniqueness) and data dictionary located in `docs/diagrams/03-erd.md`.
2. **Data Flow Diagrams (DFD Level 0 & Level 1):** Context diagram (Admin, Driver, Attendee) and 7-process functional decomposition (`1.0`–`7.0`) across data stores `D1`–`D6` located in `docs/diagrams/04-dfd.md`.

### 4.3 System Behavior & Architecture
1. **UML Class Diagram:** Models all 8 implemented PHP classes (`Database`, `User`, `Driver`, `Attendee`, `Bus`, `Tent`, `Itinerary`, `ReportGenerator`) with exact method signatures located in `docs/diagrams/05-class-diagram.md`.
2. **UML Deployment Diagram:** Physical architecture depicting Client Devices (Admin Desktop, Driver Smartphone, Attendee Smartphone) ↔ HTTP/HTTPS ↔ XAMPP Web Server (Apache + PHP 8.2.12 + PDO `pdo_sqlite`) ↔ Embedded SQLite 3 Relational Database (`data/hajj_umrah.sqlite`) located in `docs/diagrams/06-deployment-diagram.md`.

---

## Chapter 5: Implementation

### 5.1 Technology Stack & Architectural Note
* **Server-Side Logic:** PHP 8.2.12 (`declare(strict_types=1);`, modular OOP classes under `src/classes/`).
* **Database Layer:** Relational **SQLite 3** via **PHP PDO** (`PRAGMA foreign_keys = ON`). While our initial Week 3 proposal envisioned Oracle SQL, during Agile Sprint 0/1 the team elected to use embedded SQLite 3 via PDO so all three developers could run, test, and demonstrate the exact same 3NF relational schema with zero external database server configuration while retaining strict foreign keys, `CHECK` constraints, and ACID transactions.
* **Frontend:** Semantic HTML5 and custom responsive CSS3 (`src/assets/css/style.css`) with zero external CDN dependencies (runs 100% offline during live demos).

### 5.2 Key Implementation Excerpts

#### A. Secure PDO Connection, Foreign Keys & Bound Parameters (`src/classes/Database.php`)
```php
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON;');

$stmt = $this->conn->prepare($sql);
$this->bindParams($stmt, $params); // Uses PDOStatement::bindValue()
$stmt->execute();
```

#### B. Server-Side Capacity & Out-of-Service Validation (`src/classes/Bus.php` & `Attendee.php`)
```php
if (($bus['status'] ?? 'active') === 'out_of_service') {
    return ['ok' => false, 'error' => "Bus out of service: {$bus['bus_number']} cannot accept passengers."];
}
if (!$this->busModel->hasCapacity($busId, $attendeeId)) {
    return ['ok' => false, 'error' => "Bus at max capacity: {$bus['bus_number']} already has {$bus['max_capacity']}/{$bus['max_capacity']} seats occupied."];
}
```

#### C. Three-Role Access Control Guards (`src/includes/auth_check.php`)
```php
function homePathForRole(?string $role): string
{
    return match ($role) {
        'admin'  => '/admin/dashboard.php',
        'driver' => '/driver_portal.php',
        default  => '/portal.php',
    };
}
```

### 5.3 Final Implemented UI Screenshots
Live screenshots captured directly from the running application are stored in `docs/testing/screenshots/`:
* **Invalid Login Security Alert:** `docs/testing/screenshots/01_login_invalid_alert.png`
* **Admin Operations Dashboard:** `docs/testing/screenshots/02_admin_dashboard.png`
* **Bus Capacity Protection Alert:** `docs/testing/screenshots/03_bus_full_capacity_alert.png`
* **Printable Bus Passenger Manifest:** `docs/testing/screenshots/04_printable_bus_manifest.png`
* **Attendee Read-Only Portal:** `docs/testing/screenshots/05_attendee_portal.png`
* **Admin Bus Drivers & Service Status Management:** `docs/testing/screenshots/06_admin_drivers.png`
* **Bus Driver Schedule & Boarding Portal:** `docs/testing/screenshots/07_driver_portal.png`

---

## Chapter 6: Testing & Validation

### 6.1 Testing Approach
We validated the system at three levels:
1. **Static Syntax Verification:** Ran `php -l` across all 25 PHP files (**0 syntax errors**).
2. **Automated End-to-End Functional Testing (`src/tests/run_automated_tests.php`):** Executed **17 formal test cases (`TC-01`–`TC-17`)** via real HTTP requests with cookies, CSRF tokens, redirects, and DB assertions.
3. **Live Browser UI Verification:** Inspected rendered screens across Admin, Driver, and Attendee roles in Chromium to confirm layout integrity, error alerts, printable tables, and mobile responsiveness.

### 6.2 Functional Test Results Summary (**17 / 17 PASSED**)

| Test ID | Feature Tested | Expected Result | Observed Result | Status |
|---|---|---|---|---|
| **TC-01** | Admin Login (Valid) | Redirect to Admin Dashboard | HTTP 302 → `/admin/dashboard.php`; KPIs loaded | **PASS** |
| **TC-02** | Login (Invalid Password) | Show `"Invalid credentials"` error | Red error banner shown; access denied | **PASS** |
| **TC-03** | RBAC Access Control | Block Attendee from `/admin/attendees.php` | Redirected to `/portal.php` with alert | **PASS** |
| **TC-04** | Add Attendee + Account | Saved in `ATTENDEES` & `USERS` | Attendee `#13` created & searchable | **PASS** |
| **TC-05** | Full Bus Rejection | Block assignment to `BUS-101` (`5/5`) | Blocked with `"Bus at max capacity"` alert | **PASS** |
| **TC-06** | Search by National ID | Show matching attendee only | Returned only `1098765431` | **PASS** |
| **TC-07** | Attendee Portal Sync | Show updated bus/tent/itinerary live | New attendee saw `BUS-201`, `TENT-U01`, itinerary | **PASS** |
| **TC-08** | Printable Manifest & CSV | List all `BUS-101` passengers + CSV | 5/5 passengers rendered + valid `text/csv` | **PASS** |
| **TC-09** | Edit Attendee | Persist updated name/phone | Updated fields verified in directory | **PASS** |
| **TC-10** | Delete Attendee | Cascade delete from `USERS`/`ATTENDEES` | Confirmation shown; 0 records remain | **PASS** |
| **TC-11** | Full Tent Rejection | Block assignment to `TENT-A01` (`6/6`) | Blocked with `"Tent at max capacity"` alert | **PASS** |
| **TC-12** | Logout & Session Guard | Destroy session; block `/admin/*` | Redirected back to `/login.php` | **PASS** |
| **TC-13** | Add Driver & Assign Bus | Create driver account & assign to `BUS-201` | Driver `#4` added & linked to `BUS-201` | **PASS** |
| **TC-14** | Driver Portal & RBAC | Driver sees assigned bus/schedule; blocked from `/admin/*` | `/driver_portal.php` loaded; `/admin/drivers.php` blocked | **PASS** |
| **TC-15** | Out of Service Enforcement | Out-of-service bus alerts driver & blocks allocation | Alert shown on portal; allocation rejected | **PASS** |
| **TC-16** | Driver Status & Removal | Toggle driver Out of Service & delete cleanly | Status updated, then removed cleanly | **PASS** |
| **TC-17** | Logistics Driver Guard | Block OOS driver & enforce 1:1 driver-bus uniqueness | Blocked OOS driver on `logistics.php` | **PASS** |

Full testing details and screenshot references are archived in `docs/testing/test-cases-and-results.md`.

---

## Chapter 7: Conclusion & Future Work

### 7.1 Conclusion
The **Hajj and Umrah Registration System** demonstrates the complete software engineering lifecycle—from real-world problem analysis and Agile Scrum execution to 3NF SQLite relational design, six consistent SE diagrams, secure OOP PHP implementation across three distinct user roles, and 100% passing functional verification (**17/17 test cases**). By replacing manual spreadsheets and scattered chat messages with a capacity-protected operational database and dedicated Driver and Attendee portals, the system directly solves the day-to-day coordination bottlenecks of local Hajj and Umrah campaigns.

### 7.2 Future Work
1. **QR Code Bus Boarding Passes:** Embed scannable QR codes on `portal.php` so bus drivers can mark passenger boarding electronically via smartphone camera.
2. **Automated SMS / WhatsApp Alerts:** Trigger instant notifications whenever an Admin edits a bus departure time, marks a bus Out of Service, or reassigns a tent.
3. **Arabic / English Bilingual Interface (RTL Support):** Add a one-click language toggle (`EN` / `AR`) to serve both Arabic-speaking domestic pilgrims and international Umrah groups.
4. **Client-Server RDBMS Scaling:** Because all database queries are encapsulated inside `Database.php` using standard PHP PDO, large multi-campaign enterprises can point the PDO DSN to PostgreSQL, MySQL, or Oracle in production without rewriting application logic.

---

## References
1. General Authority for Statistics (GASTAT), *Hajj Statistics 1445 AH (2024)*, Kingdom of Saudi Arabia. Available at: `https://www.stats.gov.sa`
2. General Authority for Statistics (GASTAT), *Hajj Statistics 1446 AH (2025)*, Kingdom of Saudi Arabia. Available at: `https://www.stats.gov.sa`
3. Ministry of Hajj and Umrah, *Nusuk Official Digital Platform*, Kingdom of Saudi Arabia. Available at: `https://www.nusuk.sa`
4. PHP Group, *PHP Data Objects (PDO) & SQLite Functions Manual (PHP 8.2)*. Available at: `https://www.php.net/manual/en/book.pdo.php`
5. SQLite Consortium, *SQLite 3 SQL Syntax & Foreign Key Support Documentation*. Available at: `https://www.sqlite.org/foreignkeys.html`
6. Owaidah, A., Olaru, D., Bennamoun, M., Sohel, F., & Khan, N. (2023). *Transport of pilgrims during Hajj: Evidence from a discrete event simulation study*. PLOS ONE, 18(6), e0286460.
7. Sommerville, I. (2016). *Software Engineering* (10th ed.). Pearson Education.

---

## Appendices
* **Appendix A:** Academic & Operational Literature Review (`docs/evidence/academic-literature-review.md`)
* **Appendix B:** Prepared Stakeholder Interview & Pilgrim Survey Instruments (`docs/evidence/staff-interview-guide.md`, `docs/evidence/attendee-survey-form.md`)
* **Appendix C:** SQLite 3 Relational DDL & Seed Scripts (`sql/schema.sql`, `sql/seed.sql`)
* **Appendix D:** Complete Diagram Viewer & Wireframes (`docs/diagrams/diagrams-viewer.html`, `docs/diagrams/01-wireframes.html`)
