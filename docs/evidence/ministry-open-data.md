# Official Open Data — Ministry of Hajj and Umrah & GASTAT Statistics

## Purpose
Official statistics published by the **General Authority for Statistics (GASTAT)** in collaboration with the **Ministry of Hajj and Umrah** demonstrate the immense scale of pilgrim movement during each Hajj season. Even a single licensed domestic campaign serves hundreds to thousands of pilgrims within a strict 5-to-6-day window, making manual spreadsheet management unsustainable.

---

## Verified Official Pilgrim Statistics (GASTAT)

### 1. Hajj Season 1445 AH (2024)
* **Total Pilgrims:** **1,833,164**
  * **External Pilgrims (from outside KSA):** 1,611,310
  * **Domestic / Internal Pilgrims (citizens & residents):** 221,854
* **Gender Breakdown:** 958,137 Male | 875,027 Female
* **External Arrival Mode:** 1,546,345 Air | 60,251 Land | 4,741 Sea

### 2. Hajj Season 1446 AH (2025)
* **Total Pilgrims:** **1,673,230**
  * **External Pilgrims (from outside KSA):** 1,506,576
  * **Domestic / Internal Pilgrims (citizens & residents):** 166,654
* **Gender Breakdown:** 877,841 Male | 795,389 Female
* **External Arrival Mode:** 1,435,017 Air | 66,465 Land | 5,094 Sea

---

## Comparative Summary Table (For Chapter 1 of Final Report)

| Metric | Hajj 1445 AH (2024) | Hajj 1446 AH (2025) | Operational Implication for Our System |
|---|---:|---:|---|
| **Total Pilgrims** | 1,833,164 | 1,673,230 | High-density timed movements between Makkah, Mina, Arafat, and Muzdalifah require structured databases. |
| **Domestic Pilgrims** | 221,854 | 166,654 | Hundreds of licensed local campaigns each manage 300–2,000+ domestic attendees requiring bus and tent rosters. |
| **External Pilgrims** | 1,611,310 | 1,506,576 | Multi-group transport scheduling (buses of 45–50 seats) demands automated capacity checks to prevent overbooking. |

## Technical Justification from Scale Data
1. **Relational Integrity (Oracle SQL):** Managing 500–2,000 attendees across 10–40 buses and 25–100 tents in a single campaign creates thousands of relational links (`ATTENDEE → BUS → ITINERARY` and `ATTENDEE → TENT`). A relational database with Primary Keys, Foreign Keys, and capacity checks prevents orphaned records and seat double-booking.
2. **Real-Time Read-Only Portal:** With strict dispatch windows (e.g., Tarwiyah Day move to Mina, Arafat Day departure), pushing schedule updates once to a centralized database instantly updates every pilgrim's phone without relying on buried WhatsApp chats.

## Official Sources
1. General Authority for Statistics (GASTAT), *Hajj Statistics 1445 AH (2024)* — `https://www.stats.gov.sa`
2. General Authority for Statistics (GASTAT), *Hajj Statistics 1446 AH (2025)* — `https://www.stats.gov.sa`
3. Ministry of Hajj and Umrah — Nusuk Official Portal — `https://www.nusuk.sa`
