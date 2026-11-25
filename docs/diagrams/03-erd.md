# Entity-Relationship Diagram (ERD) & Data Dictionary

The relational schema consists of **7 normalized (3NF) tables** implemented in **SQLite 3 via PHP PDO** (`sql/schema.sql`) with `PRAGMA foreign_keys = ON`.

```mermaid
erDiagram
    USERS ||--o| ATTENDEES : "authenticates pilgrim (1:0..1)"
    USERS ||--o| DRIVERS : "authenticates driver (1:0..1)"
    DRIVERS ||--o| BUSES : "operates (1:0..1)"
    BUSES ||--o{ ATTENDEES : "transports (1:N)"
    TENTS ||--o{ ATTENDEES : "accommodates (1:N)"
    BUSES ||--o{ ITINERARIES : "follows schedule (1:N)"
    ATTENDEES ||--o{ SUPPORT_TICKETS : "submits ticket (1:N)"

    USERS {
        INTEGER user_id PK "AUTOINCREMENT"
        TEXT username UK "NOT NULL"
        TEXT password_hash "NOT NULL (bcrypt)"
        TEXT role "NOT NULL ('admin' | 'attendee' | 'driver')"
        TEXT created_at "DEFAULT CURRENT_TIMESTAMP"
    }

    DRIVERS {
        INTEGER driver_id PK "AUTOINCREMENT"
        INTEGER user_id FK "UNIQUE NOT NULL -> USERS"
        TEXT full_name "NOT NULL"
        TEXT license_number UK "NOT NULL"
        TEXT phone "NOT NULL"
        TEXT status "NOT NULL ('active' | 'out_of_service')"
        TEXT created_at "DEFAULT CURRENT_TIMESTAMP"
    }

    BUSES {
        INTEGER bus_id PK "AUTOINCREMENT"
        TEXT bus_number UK "NOT NULL (e.g., BUS-101)"
        INTEGER driver_id FK "UNIQUE NULLABLE -> DRIVERS"
        INTEGER max_capacity "NOT NULL CHECK (>0)"
        TEXT departure_time "NOT NULL"
        TEXT status "NOT NULL ('active' | 'out_of_service')"
    }

    TENTS {
        INTEGER tent_id PK "AUTOINCREMENT"
        TEXT tent_number UK "NOT NULL (e.g., TENT-A01)"
        TEXT location_zone "NOT NULL"
        INTEGER max_capacity "NOT NULL CHECK (>0)"
    }

    ATTENDEES {
        INTEGER attendee_id PK "AUTOINCREMENT"
        INTEGER user_id FK "UNIQUE NOT NULL -> USERS"
        TEXT full_name "NOT NULL"
        TEXT national_id UK "NOT NULL"
        TEXT phone "NOT NULL"
        TEXT campaign_type "NOT NULL ('Hajj' | 'Umrah')"
        INTEGER bus_id FK "NULLABLE -> BUSES"
        INTEGER tent_id FK "NULLABLE -> TENTS"
        TEXT registered_at "DEFAULT CURRENT_TIMESTAMP"
    }

    ITINERARIES {
        INTEGER itinerary_id PK "AUTOINCREMENT"
        TEXT title "NOT NULL"
        TEXT event_date "NOT NULL"
        TEXT description "NOT NULL"
        INTEGER bus_id FK "NOT NULL -> BUSES"
    }

    SUPPORT_TICKETS {
        INTEGER ticket_id PK "AUTOINCREMENT"
        INTEGER attendee_id FK "NOT NULL -> ATTENDEES"
        TEXT category "NOT NULL"
        TEXT priority "NOT NULL ('normal' | 'urgent')"
        TEXT subject "NOT NULL"
        TEXT message "NOT NULL"
        TEXT status "NOT NULL ('open' | 'in_progress' | 'resolved')"
        TEXT admin_reply "NULLABLE"
    }
```

## Cardinality & Referential Integrity Rules
1. **`USERS` (1) ↔ (0..1) `ATTENDEES` / `DRIVERS`:** Every attendee and every bus driver has exactly one linked login account (`ON DELETE CASCADE`).
2. **`DRIVERS` (1) ↔ (0..1) `BUSES`:** Enforced by `BUSES.driver_id UNIQUE` (`ON DELETE SET NULL`), guaranteeing one driver cannot be double-booked across two buses simultaneously and removing a driver never deletes the bus or its passengers.
3. **`BUSES` & `DRIVERS` Service Status:** Both tables enforce `CHECK (status IN ('active', 'out_of_service'))`. Out-of-service drivers cannot be assigned to buses, and out-of-service buses block new passenger allocations while alerting both drivers and attendees.
4. **`BUSES` / `TENTS` (1) ↔ (0..N) `ATTENDEES`:** Enforced via server-side capacity checks against `max_capacity`.
5. **`BUSES` (1) ↔ (0..N) `ITINERARIES`:** Each bus carries scheduled itinerary events displayed live on both the Driver Portal and Attendee Portal (`ON DELETE CASCADE`).
6. **`ATTENDEES` (1) ↔ (0..N) `SUPPORT_TICKETS`:** Pilgrims submit structured customer support messages linked via `attendee_id` (`ON DELETE CASCADE`), allowing Campaign Administrators to inspect assigned bus/tent context, update status, and post official replies without data duplication.
