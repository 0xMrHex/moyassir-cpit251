# Final Presentation & Live Demo Playbook (Week 12+)

**Course:** CPIT251 — Software Engineering (Section IT2)  
**Instructor:** Prof. Mohammed J. Alhaddad  
**Project:** Moyassir (ميسر) — Hajj and Umrah Campaign Logistics System (PHP 8.2 + SQLite 3 PDO)  
**Presenters:** Mohammed Aljedani (`2435544`), Omar Alofi (`2436990`), Saud Faisal Almesfer (`2435484`)

---

## Part 1: 12-Slide PowerPoint Deck Blueprint (Equal Team Split)

| Slide # | Title | Key Visual / Talking Points | Speaker |
|---|---|---|---|
| **1** | **Title & Team** | Project Title, CPIT251, FCIT KAU, Prof. Mohammed J. Alhaddad, team names, IDs, and Scrum roles. | Mohammed |
| **2** | **Problem Statement & Real-World Scale** | Fragmented Excel/paper/WhatsApp pain points + GASTAT Official Open Data (**1.83M** Hajj 1445 AH; **1.67M** Hajj 1446 AH; **166K–221K** domestic pilgrims). | Mohammed |
| **3** | **Supporting Evidence & Nusuk Gap Analysis** | Side-by-side table: Nusuk (national permits & payments) vs. our system (internal bus manifests, driver dispatch, tent zones, capacity limits) + interview/survey takeaways. | Mohammed |
| **4** | **Proposed Solution & 3 User Roles** | Overview of Admin Dashboard, Bus Driver Schedule Portal, and Attendee Read-Only Portal; highlight **8 core functions** (exceeding minimum 4). | Omar |
| **5** | **Agile (Scrum) Methodology & Sprints** | Sprint 0 → Sprint 4 roadmap + Agile decision to adopt portable embedded **SQLite 3 (PDO)** for zero-config team collaboration while enforcing 3NF relational integrity. | Omar |
| **6** | **UI Design: Wireframes & Screen Flow** | Wireframes from `01-wireframes.html` (9 screens) + `02-screen-flow.md` showing Admin, Driver, and Attendee role separation. | Saud |
| **7** | **Database Design: ERD & 3NF Schema** | Exported ERD (`03-erd.md`) highlighting 6 normalized tables (`USERS`, `DRIVERS`, `BUSES`, `TENTS`, `ATTENDEES`, `ITINERARIES`), `PRAGMA foreign_keys = ON`, `UNIQUE(driver_id)`, and `CHECK` constraints. | Mohammed |
| **8** | **Data Flow Diagrams (Level 0 & Level 1)** | Exported DFD (`04-dfd.md`) tracing processes `1.0`–`7.0` and data stores `D1`–`D6` across Admin, Driver, and Attendee entities. | Mohammed |
| **9** | **System Architecture: Class & Deployment Diagrams** | Exported UML Class Diagram (`05-class-diagram.md` — 8 OOP PHP classes) + Deployment Diagram (`06-deployment-diagram.md`: Browser → Apache/PHP 8.2 + PDO → SQLite 3). | Omar |
| **10** | **Implementation Highlights (Security & Business Rules)** | Code snippets: PDO prepared statements (`bindValue`), `password_hash()`/`password_verify()`, CSRF tokens, capacity validation, and Out-of-Service enforcement. | Omar |
| **11** | **Testing & Validation Results** | **17/17 Test Cases PASSED** summary table + live screenshots (`02_admin_dashboard.png`, `03_bus_full_capacity_alert.png`, `06_admin_drivers.png`, `07_driver_portal.png`). | Saud |
| **12** | **Live Demo Transition & Conclusion** | Switch to browser for 5-minute live demo → Wrap up with Future Work (QR boarding, SMS/WhatsApp alerts, Arabic RTL). | Saud (Demo) + All |

---

## Part 2: 5-Minute Live Demonstration Script

### Pre-Demo Checklist (2 Minutes Before Presenting)
1. Start the PHP server:
   ```powershell
   & 'C:\xampp\php\php.exe' -S 127.0.0.1:8088 -t 'c:\Users\3rekh\Documents\antigravity\cpit251-hajj-umrah\src'
   ```
2. Open `http://127.0.0.1:8088/login.php` in Chrome.
3. Have VS Code open to `src/classes/Database.php` and `src/classes/Bus.php` for quick code walkthrough questions.

### Step-by-Step Demo Flow (~5 Minutes)
1. **[0:00–0:45] Security & Admin Login (Omar):**
   * Enter `admin` + wrong password → Show red **"Invalid credentials"** error banner (`TC-02`).
   * Log in with `admin` / `Admin@2026` → Show **Admin Operations Dashboard** (`12` attendees, `3` drivers, `BUS-101` 5/5 **FULL**, `TENT-A01` 6/6 **FULL**).
2. **[0:45–1:45] Register New Attendee & Capacity Protection (Mohammed):**
   * Click **+ Register Attendee** → Add a live test pilgrim (e.g., `Khalid Demo`, National ID `1099887766`, Phone `0551234567`, `BUS-102`, `TENT-B02`, Password `Pilgrim@2026`) and search for `1099887766`.
   * Open **Allocate Bus/Tent** → Try moving them to **`BUS-101 (5/5 — FULL)`** → Show the red rejection alert: **"Bus at max capacity: BUS-101 already has 5/5 seats occupied."**
3. **[1:45–2:45] Bus Drivers & Out-of-Service Control (Omar):**
   * Open **Bus Drivers** → Show the 3 registered drivers, 1:1 bus assignment dropdowns, and **Bus Fleet Service Status Control** (mention toggling **Out of Service** blocks allocations and alerts drivers/pilgrims).
   * Open **Manifests & Reports** → Show printable `BUS-101` Passenger List (`Print / Save as PDF` + `Export CSV`).
4. **[2:45–3:45] Bus Driver Schedule Portal (Saud):**
   * Sign out → Log in as **Bus Driver** (`driver1` / `Driver@2026`).
   * Show `driver_portal.php`: Driver status (`ACTIVE`), assigned `BUS-101` (`5/5 seats`), 3-step itinerary timeline, and passenger boarding checklist.
5. **[3:45–5:00] Attendee Read-Only Portal & RBAC Proof (Saud):**
   * Sign out → Log in as the **Attendee** (`1099887766` / `Pilgrim@2026`).
   * Show their mobile-friendly Pilgrim Pass (`BUS-102`, `TENT-B02`, driver phone, and live schedule).
   * Type `http://127.0.0.1:8088/admin/dashboard.php` in the address bar → Prove RBAC redirects them back to `/portal.php` with **Access Denied**!

---

## Part 3: Team Q&A Preparation (Anticipated Instructor Questions)

1. **"Your proposal mentioned Oracle SQL, why does the final system use SQLite?"** *(Mohammed / Omar)*  
   During Sprint 0/1 in our Agile process, we transitioned to **SQLite 3 via PHP PDO** (`PRAGMA foreign_keys = ON`) so all three team members could clone and run the exact same relational database across laptops with zero server setup friction, while keeping full 3NF tables, foreign keys, `UNIQUE`/`CHECK` constraints, and ACID transactions—and all 6 diagrams in our report match this exact implementation.
2. **"How do you prevent SQL Injection?"** *(Omar)*  
   Every query in `src/classes/Database.php` uses PDO prepared statements (`$pdo->prepare()` + `$stmt->bindValue()`). User input is never concatenated into SQL strings.
3. **"What happens if an Admin edits an attendee already on a full bus, or assigns an Out-of-Service driver?"** *(Omar / Saud)*  
   `Bus::getOccupancy()` excludes the attendee's own `attendee_id` so they don't block their own seat, and `Bus::validateDriverAssignable()` blocks assigning any `out_of_service` driver on both the Drivers and Logistics pages while `UNIQUE(driver_id)` prevents assigning one driver to two buses.
4. **"Why build this if the Nusuk app already exists?"** *(Mohammed)*  
   Nusuk manages national permits, eligibility, and package payments. Local licensed campaigns still need an internal database for private bus seat manifests, driver dispatch schedules, specific Mina tent numbers, and printable ground-staff checklists.
5. **"How are passwords and forms secured?"** *(Saud)*  
   Passwords use `password_hash($pw, PASSWORD_BCRYPT)` and `password_verify()`, login regenerates the PHP session ID, all POST forms verify cryptographic CSRF tokens, and all HTML output is escaped via `htmlspecialchars()`.
