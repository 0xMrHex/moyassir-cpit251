# Academic & Operational Literature Evidence — Hajj Transport & Data Systems

## Purpose
In accordance with Section `3.d` of the CPIT251 Course Project Announcement (*"Provide appropriate evidence supporting the need for the proposed system. This may include academic or professional references, photographs, observations, existing systems..."*), this document synthesizes peer-reviewed academic research justifying the **Moyassir (ميسر)** campaign logistics system.

---

## 1. Peer-Reviewed Hajj Transportation Bottleneck Research
* **Study:** Owaidah, A., Olaru, D., Bennamoun, M., Sohel, F., & Khan, N. (2023). *"Transport of pilgrims during Hajj: Evidence from a discrete event simulation study."* **PLOS ONE**, 18(6), e0286460.
* **Key Findings Relevant to Moyassir (ميسر):**
  1. **Cohort-Based Movement Constraints:** Unlike everyday urban public transit where riders arrive randomly, Hajj pilgrims travel in strict pre-assigned cohorts tied to specific camps and timed dispatch windows across Makkah, Mina, Arafat, and Muzdalifah (*Al-Nafrah*).
  2. **Cascading Schedule Delays:** Discrete-event simulations demonstrate that unclear boarding manifests, delayed driver dispatch, or timetable miscommunication at camp gates cause cascading congestion across subsequent ritual phases.
  3. **Capacity Matching ("Bus Packing"):** Matching exact cohort headcounts to vehicle seat capacities before dispatch is essential to prevent terminal queues and stranded pilgrims—directly supporting Moyassir's server-side `Bus::hasCapacity()` and `Out of Service` fleet controls.

---

## 2. Operational Risks of Manual Spreadsheets in Logistics
* **Software Engineering Principle (Sommerville, 2016):** Decentralized spreadsheet files lack referential integrity, concurrency control, and role-based access views. When multiple coordinators edit Excel lists and broadcast schedule changes over messaging apps:
  * **Referential Drift:** Deleting or editing a driver or bus in one sheet leaves orphaned or contradictory assignments in pilgrim lists.
  * **Overbooking Errors:** Manual row counting fails to enforce hard upper bounds on bus seats or tent capacity.
  * **Information Latency:** Attendees and bus drivers view stale screenshots rather than live database records.

## 3. Direct Alignment with Moyassir (ميسر)
| Documented Problem in Literature | Moyassir (ميسر) Engineering Solution |
|---|---|
| Cohort-to-bus seat mismatch & overbooking (Owaidah et al., 2023) | Automated `COUNT(*)` vs. `max_capacity` validation in `Bus.php` & `Tent.php` |
| Cascading dispatch delays from missed schedule changes | Instant live itinerary updates across `/driver_portal.php` and `/portal.php` |
| Vehicle breakdown communication gaps | Real-time `Out of Service` status toggle alerting drivers & pilgrims while blocking new assignments |
| Referential drift in manual spreadsheets | Normalized 6-table 3NF SQLite 3 relational schema with `PRAGMA foreign_keys = ON` |
