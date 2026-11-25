# Sprint 3 Preview & Review — Attendee Portal, Search, Logistics & Reports (Weeks 8–9)

**Sprint Goal:** Complete all remaining user stories (**US-04 Search & Filtering**, **US-05 Read-Only Attendee Portal**, **US-06 Printable Manifests & CSV Reports**, and **US-07 Bus/Tent/Itinerary Management**) and apply responsive UI polish.  
**Status:** Completed

---

## 1. Features Delivered in Sprint 3

1. **Attendee Read-Only Portal (`src/portal.php` — US-05):**
   * Mobile-first responsive Pilgrim Pass displaying personal identity, assigned Bus Number, Driver Name & clickable phone link, Departure Time, Tent Number, Location Zone, and a chronological **Live Personal Itinerary Timeline**.
   * Strictly read-only (`requireAttendee()`): attendees cannot edit data or access any `/admin/*` endpoint.
2. **Multi-Criteria Search & Filtering (`src/admin/attendees.php` — US-04):**
   * Case-insensitive `LOWER(...) LIKE :q` bound search across Full Name, National ID, and Phone, combinable with Bus, Tent, and Campaign Type (`Hajj` / `Umrah`) filters.
3. **Printable Bus Manifests, Tent Rosters & CSV Export (`src/admin/reports.php` — US-06):**
   * Generates formatted ground-staff boarding manifests for any Bus or check-in rosters for any Tent.
   * Supports **Print / Save as PDF** (`window.print()` with dedicated `@media print` stylesheet that strips navigation chrome) and **Download CSV** (`?export=csv`).
4. **Campaign Logistics & Itinerary Management (`src/admin/logistics.php` — US-07):**
   * Allows Admins to update bus departure times, drivers, and capacities (preventing shrinking capacity below current occupancy), add new buses/tents, and publish/remove bus itinerary events.
   * Enables real-time Admin-to-Attendee propagation: updating a bus departure time or tent immediately appears when the attendee refreshes `portal.php`.
5. **Responsive UI Polish (`src/assets/css/style.css`):**
   * Cohesive emerald/slate theme with clear visual capacity progress bars, FULL/AVAILABLE badges, accessible alerts, and mobile responsiveness.

---

## Sprint 3 Exit Gate Check
- [x] Mobile-friendly read-only Attendee Portal (`portal.php`) complete
- [x] Search & multi-dropdown filters active on `admin/attendees.php`
- [x] Bus Manifest & Tent Roster with Print/PDF + CSV export active on `admin/reports.php`
- [x] Logistics & Itinerary management active on `admin/logistics.php`
- [x] Zero PHP syntax warnings across all source files (verified next in Sprint 4)
