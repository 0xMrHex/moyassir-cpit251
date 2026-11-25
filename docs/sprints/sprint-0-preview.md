# Sprint 0 Preview & Review — Setup, Research & Evidence (Weeks 1–3)

**Sprint Goal:** Complete project setup, verify the local PHP + SQLite 3 (PDO) environment, establish Scrum roles & Product Backlog, and assemble real-world supporting evidence.  
**Status:** Completed (ready for team field data insertion on interviews/surveys)

---

## 1. Environment & Stack Verification (Executed on Local Machine)

| Component | Detected Status | Verified Details |
|---|---|---|
| **PHP Runtime** | Installed & Ready | `C:\xampp\php\php.exe` — **PHP 8.2.12** (ZTS Visual C++ 2019 x64) |
| **SQLite 3 PDO (`pdo_sqlite`)** | Enabled & Active | Selected as portable 3NF relational database engine (`PRAGMA foreign_keys = ON`) for zero-config multi-laptop team development |
| **Git** | Installed | `git version 2.21.0.windows.1` |

---

## 2. Repository & Folder Structure Created
Created isolated workspace root `cpit251-hajj-umrah/`:
* `/docs` — Evidence, diagrams, sprint previews, testing artifacts, Final Project Report, Presentation guide
* `/sql` — Relational SQLite 3 DDL (`schema.sql`) and seed scripts (`seed.sql`)
* `/src` — Modular OOP PHP application (`config/`, `classes/`, `includes/`, `admin/`, `assets/`, `tests/`)
* `/data` — Auto-initialized `hajj_umrah.sqlite` database file

---

## 3. Scrum Governance & Backlog Finalized
* **Product Owner & Database Lead:** Mohammed Aljedani (`2435544`)
* **Scrum Master & Backend Lead:** Omar Alofi (`2436990`)
* **Frontend & QA Lead:** Saud Faisal Almesfer (`2435484`)
* **Product Backlog:** 8 User Stories (`US-01` to `US-08`), covering 8 meaningful system functions (exceeding the minimum 4 required by CPIT251) across Admin, Bus Driver, and Attendee roles.

---

## 4. Supporting Evidence Assembled
1. **Nusuk Existing System Analysis** (`docs/evidence/nusuk-analysis.md`) — Detailed comparison proving our system complements Nusuk rather than duplicating national permit workflows.
2. **Official Ministry / GASTAT Open Data** (`docs/evidence/ministry-open-data.md`) — Verified Hajj 1445 AH (**1,833,164** total; **221,854** domestic) and Hajj 1446 AH (**1,673,230** total; **166,654** domestic) pilgrim counts justifying relational DB scale.
3. **Structured Staff Interview Kit** (`docs/evidence/staff-interview-guide.md`) — 5 structured questions + response log ready for 2–3 local campaign staff interviews.
4. **Attendee Survey Instrument** (`docs/evidence/attendee-survey-form.md`) — 6-question Google Form script + results table ready to collect past pilgrim responses & export PNG charts.

---

## Sprint 0 Exit Gate Check
- [x] Folder structure `/docs`, `/sql`, `/src`, `/data` initialized
- [x] PHP 8.2 + PDO SQLite 3 verified on workstation
- [x] Scrum roles and Product Backlog finalized in `README.md`
- [x] Supporting evidence files created for Chapter 1 of Final Report
