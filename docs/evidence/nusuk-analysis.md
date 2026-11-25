# Existing System Analysis: The Nusuk Platform

## 1. Overview of Nusuk
**Nusuk** (`https://www.nusuk.sa`) is the official digital gateway supervised by the **Ministry of Hajj and Umrah** in the Kingdom of Saudi Arabia. Integrated with the National Single Sign-On (*Nafath*) and electronic payment systems (*SADAD*), Nusuk handles national-level regulatory workflows for citizens, residents, and international pilgrims:
* Verifying pilgrim eligibility (minimum age, prior-Hajj priority rules, mandatory health vaccinations).
* Browsing certified domestic Hajj packages and licensed service providers.
* Completing electronic package payments and issuing official **Hajj and Umrah permits** (*No Hajj Without Permit* compliance).

## 2. Gap Analysis: National Platform vs. Internal Campaign Operations
While Nusuk excels at national permit issuance and macro-level package enrollment, **individual licensed Hajj & Umrah campaigns** must still coordinate micro-level, day-to-day field logistics for their own enrolled group of hundreds or thousands of pilgrims. Currently, many campaign offices fill this gap using disconnected Excel files, printed clipboards, and WhatsApp broadcast groups.

| Operational Capability | Nusuk Platform (National Level) | Local Campaign Reality (Manual) | Proposed System (Hajj & Umrah Registration) |
|---|---|---|---|
| Official Hajj/Umrah Permits & Eligibility | **Full Support** (Nafath + Ministry verification) | Relies on Nusuk | Complements Nusuk (tracks registered permit holders) |
| Package Selection & SADAD Payment | **Full Support** | Relies on Nusuk | Out of scope (handled by Nusuk) |
| **Internal Bus Seat Manifests & Driver Contacts** | Not managed per private vehicle | Scattered Excel / Paper | **Centralized DB + Live Capacity Limits** |
| **Specific Tent Number & Zone Allocation (Mina/Arafat)** | Camp-level package tier only | Paper charts / WhatsApp | **Per-Attendee Tent Allocation + Occupancy Tracking** |
| **Dynamic Itinerary & Departure Time Broadcast** | General Ministry notices | WhatsApp messages (easily missed) | **Instant Admin Edit → Live Attendee Portal Update** |
| **Printable Ground-Staff Rosters (Bus/Tent)** | Not tailored to local bus captains | Manual copy-paste | **One-Click Printable PDF & CSV Manifests** |
| **Overbooking Prevention (Seat / Bed Caps)** | Package quota only | Prone to human counting errors | **Automated Server-Side Capacity Validation** |

## 3. Conclusion
Our system **does not duplicate or replace Nusuk**. Instead, it serves as the **internal operational database** that local Hajj and Umrah campaign managers need *after* pilgrims register through Nusuk—eliminating manual errors, double-bookings, and communication breakdowns on the ground.
