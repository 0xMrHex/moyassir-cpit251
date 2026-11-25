# Sprint 1 Preview & Review — Analysis, UI/UX & System Design (Weeks 4–5)

**Sprint Goal:** Produce all 6 mandatory Software Engineering diagrams and build the normalized 3NF SQLite 3 database schema & seed scripts.  
**Status:** Completed

---

## 1. All 6 Required Software Engineering Diagrams Produced

| Course Requirement Area | Diagram Deliverable | File Path |
|---|---|---|
| **UI & Design (1)** | Wireframes / UI Mockups (9 screens across Admin, Driver & Attendee roles) | `docs/diagrams/01-wireframes.html` |
| **UI & Design (2)** | Screen Flow Diagram + Role Access Matrix | `docs/diagrams/02-screen-flow.md` |
| **Database & Data Design (1)** | Entity-Relationship Diagram (ERD) + 3NF Data Dictionary | `docs/diagrams/03-erd.md` |
| **Database & Data Design (2)** | Data Flow Diagram (Level 0 Context + Level 1 Decomposition) | `docs/diagrams/04-dfd.md` |
| **System Behavior & Interaction (1)** | UML Class Diagram (8 PHP OOP classes) | `docs/diagrams/05-class-diagram.md` |
| **System Behavior & Interaction (2)** | UML Deployment Diagram (Client Browsers → HTTP → XAMPP Apache/PHP 8.2 + PDO → SQLite 3) | `docs/diagrams/06-deployment-diagram.md` |
| **Export Helper** | Standalone HTML Viewer to print/export all diagrams to PDF/PNG for Report & PowerPoint | `docs/diagrams/diagrams-viewer.html` |

---

## 2. Relational SQLite 3 Database Schema & Seed Scripts Built

* **`sql/schema.sql`** — 3NF relational DDL (`PRAGMA foreign_keys = ON`) creating all 6 tables (`USERS`, `DRIVERS`, `BUSES`, `TENTS`, `ATTENDEES`, `ITINERARIES`) using `INTEGER PRIMARY KEY AUTOINCREMENT`, Foreign Keys (`ON DELETE CASCADE` / `ON DELETE SET NULL`), `UNIQUE` constraints (including `BUSES.driver_id UNIQUE`), `CHECK` constraints (`role`, `status`, `campaign_type`, `max_capacity > 0`), and 5 performance indexes.
* **`sql/seed.sql`** — Inserts:
  * **1 Admin** (`admin` / `Admin@2026`), **3 Bus Drivers** (`driver1`–`driver3` / `Driver@2026`), and **12 Attendees** (`1098765431`–`1098765442` / `Pilgrim@2026`)
  * **3 Buses:** `BUS-101` (`5/5` — **FULL**, ready to demo capacity check!), `BUS-102` (`5/10`), `BUS-201` (`2/8`, Umrah)
  * **3 Tents:** `TENT-A01` (`6/6` — **FULL**, ready to demo tent capacity check!), `TENT-B02` (`4/10`), `TENT-U01` (`2/8`, Umrah)
  * **6 Itineraries** linked across the 3 buses.

---

## Sprint 1 Exit Gate Check
- [x] All 6 mandatory SE diagrams created and stored in `/docs/diagrams/`
- [x] Normalized DDL (`sql/schema.sql`) and seed data (`sql/seed.sql`) authored with strict FK/CHECK/UNIQUE constraints
- [x] Verified schema creation and seed row counts via PHP PDO
