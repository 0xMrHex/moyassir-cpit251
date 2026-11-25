# Structured Staff Interview Guide & Response Log

> **Academic Integrity Note:** Use the 5 structured questions below to interview **2–3 actual administrative employees or ground workers** from local Hajj/Umrah campaigns. Record their real answers in the log table below and paste the summarized findings into Chapter 1 & Appendix A of your Final Project Report.

---

## Interview Protocol
* **Target Participants:** Campaign Coordinators, Bus Supervisors, or Administrative Registration Clerks (2–3 interviewees).
* **Duration:** 10–15 minutes per interview (in-person or phone).
* **Objective:** Identify daily workflow bottlenecks, manual record-keeping errors, and critical software requirements.

## 5 Structured Interview Questions
1. **Current Tools:** *What tools do you currently use to record pilgrim details, bus manifests, and tent allocations (e.g., Excel spreadsheets, printed paper lists, WhatsApp groups, or custom software)?*
2. **Most Frequent Errors:** *What are the most common data or logistical mistakes that happen during the campaign (e.g., overbooking a bus, duplicate entries, pilgrims going to the wrong tent)?*
3. **Schedule Changes:** *When a bus departure time or tent assignment changes last minute, how do you currently notify attendees, and what difficulties arise?*
4. **Search & Lookup Speed:** *When a pilgrim calls or approaches the desk asking for their bus number or tent zone, how long does it take to look up their record?*
5. **Must-Have Features:** *If you had a simple web dashboard with a pilgrim read-only portal, which 2–3 features would save your staff the most time?*

---

## Field Response Log (Fill After Conducting Interviews)

| Question | Interviewee 1 (Role: `[e.g., Operations Coordinator]`) | Interviewee 2 (Role: `[e.g., Bus Supervisor]`) | Interviewee 3 (Role: `[e.g., Admin Clerk]`) |
|---|---|---|---|
| **Q1. Current Tools** | `[Record real response]` | `[Record real response]` | `[Record real response]` |
| **Q2. Frequent Errors** | `[Record real response]` | `[Record real response]` | `[Record real response]` |
| **Q3. Schedule Changes** | `[Record real response]` | `[Record real response]` | `[Record real response]` |
| **Q4. Lookup Speed** | `[Record real response]` | `[Record real response]` | `[Record real response]` |
| **Q5. Must-Have Features** | `[Record real response]` | `[Record real response]` | `[Record real response]` |

## Mapping Expected Pain Points to System Features
* Fragmented Excel/WhatsApp lists → **Centralized Oracle SQL Database (`schema.sql`)**
* Bus/Tent overbooking → **Automated Server-Side Capacity Validation (`admin/allocate.php`)**
* Missed schedule updates → **Read-Only Attendee Portal (`portal.php`)**
* Slow manual lookup → **Instant Multi-Criteria Search & Filtering (`admin/attendees.php`)**
* Paper roster preparation → **One-Click Printable Bus & Tent Manifests (`admin/reports.php`)**
