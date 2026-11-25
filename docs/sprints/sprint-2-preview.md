# Sprint 2 Preview & Review — Core Backend & Admin Dashboard (Weeks 6–7)

**Sprint Goal:** Deliver a working, secure PHP 8.2 + SQLite 3 (PDO) Admin backend implementing **US-01 (Login & RBAC)**, **US-02 (Attendee CRUD)**, and **US-03 (Bus & Tent Allocation with Capacity Validation)**.  
**Status:** Completed

---

## 1. Core Architecture & Security Implemented

* **`src/config/db.php` & `src/classes/Database.php`**
  * Singleton PDO connection wrapper enforcing `PRAGMA foreign_keys = ON`, prepared statements (`PDOStatement::bindValue`) on 100% of queries to prevent SQL injection, auto-initialization from `sql/schema.sql` & `sql/seed.sql`, and nested transaction tracking.
* **OOP Domain Models (`src/classes/`):** `User.php`, `Driver.php`, `Attendee.php`, `Bus.php`, `Tent.php`, `Itinerary.php`, `ReportGenerator.php` — 100% matching the Class Diagram.
* **Authentication & Access Control (`src/login.php`, `src/logout.php`, `src/includes/auth_check.php`):**
  * Passwords hashed via `password_hash(..., PASSWORD_BCRYPT)` and verified via `password_verify()`.
  * Session fixation protection (`session_regenerate_id(true)`), role-based guards (`requireAdmin()`, `requireDriver()`, `requireAttendee()`), CSRF token checks on all mutating POST forms, and output escaping (`e()`).

---

## 2. Admin Features Delivered

1. **Admin Operations Dashboard (`src/admin/dashboard.php`):** Displays KPI cards (Registered Attendees, Hajj/Umrah split, Campaign Buses, Registered Drivers, Pending Allocations), color-coded bus & tent capacity progress bars, and recent registrations.
2. **Attendee Directory & CRUD (`src/admin/attendees.php`, `add_attendee.php`, `edit_attendee.php`, `delete_attendee.php`):**
   * Adding an attendee runs inside a database transaction that **automatically creates their linked `USERS` login account** (username = National ID) so they can immediately sign in to the Attendee Portal.
   * Editing an attendee syncs any National ID change back to `USERS.username` and supports optional password reset.
   * Deleting an attendee removes the parent `USERS` row (`ON DELETE CASCADE`) cleanly.
3. **Bus & Tent Allocation (`src/admin/allocate.php`):**
   * Server-side `Bus::hasCapacity()` and `Tent::hasCapacity()` query `COUNT(*)` against `max_capacity` and reject overbooking or out-of-service buses with clear alerts.
   * Properly excludes the attendee's own current assignment (`attendee_id <> :exclude_id`) to prevent false-positive self-blocking when re-saving an attendee already on a full bus/tent.

---

## Sprint 2 Exit Gate Check
- [x] Reusable `Database` PDO class using prepared bound queries
- [x] Secure bcrypt login/logout and strict RBAC guards
- [x] Full Attendee CRUD with automatic portal account provisioning
- [x] Bus & Tent allocation with server-side capacity validation verified
