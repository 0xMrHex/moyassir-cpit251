# Screen Flow Diagram — Moyassir (ميسر) Hajj & Umrah System

This diagram illustrates navigation paths, authentication guards (`auth_check.php`), and strict three-role separation between **Campaign Managers (Admins)**, **Bus Drivers**, and **Attendees (Pilgrims)**.

```mermaid
flowchart TD
    Start(["Browser Request /index.php"]) --> AuthCheck{"Active PHP Session?"}
    AuthCheck -- "No" --> Login["Screen 1: Login Page<br/>(login.php)"]
    AuthCheck -- "Yes (Admin)" --> AdminDash["Screen 2: Admin Dashboard<br/>(admin/dashboard.php)"]
    AuthCheck -- "Yes (Driver)" --> DriverPortal["Screen 8: Driver Schedule Portal<br/>(driver_portal.php)"]
    AuthCheck -- "Yes (Attendee)" --> Portal["Screen 7: Attendee Read-Only Portal<br/>(portal.php)"]

    Login --> Verify{"Verify Credentials & Role<br/>(password_verify)"}
    Verify -- "Invalid" --> LoginErr["Show Error Alert<br/>(Stay on login.php)"]
    LoginErr --> Login
    Verify -- "Valid: role = admin" --> AdminDash
    Verify -- "Valid: role = driver" --> DriverPortal
    Verify -- "Valid: role = attendee" --> Portal

    subgraph AdminArea["Admin Workspace (Guarded by requireAdmin())"]
        AdminDash --> AttendeesList["Screen 3: Attendees List & Search<br/>(admin/attendees.php)"]
        AdminDash --> Allocate["Screen 4: Bus & Tent Allocation<br/>(admin/allocate.php)"]
        AdminDash --> DriversPage["Screen 4B: Bus Drivers & Service Status<br/>(admin/drivers.php)"]
        AdminDash --> Logistics["Screen 5: Buses, Tents & Itineraries<br/>(admin/logistics.php)"]
        AdminDash --> Reports["Screen 6: Manifest & Roster Reports<br/>(admin/reports.php)"]

        AttendeesList --> AddEditDel["Add / Edit / Delete Attendee"]
        AddEditDel --> AttendeesList

        DriversPage --> DriverActions["Add/Remove Driver, Assign to Bus,<br/>Toggle Active / Out of Service"]
        DriverActions --> DriversPage

        Allocate --> CapCheck{"Bus Active & Capacity<br/>Available?"}
        CapCheck -- "Full or Out of Service" --> CapErr["Show Rejection Alert"]
        CapCheck -- "Valid" --> SaveAlloc["Commit Allocation to DB"]
        SaveAlloc --> Allocate

        Reports --> PrintPDF["Print / Save PDF & CSV"]
    end

    subgraph DriverArea["Driver Workspace (Guarded by requireDriver())"]
        DriverPortal --> ViewBus["View Assigned Bus, Status,<br/>Itinerary Schedule & Passenger List"]
    end

    subgraph AttendeeArea["Attendee Workspace (Guarded by requireAttendee())"]
        Portal --> ViewCard["View Personal Profile,<br/>Assigned Bus & Driver,<br/>Tent Zone & Live Itinerary"]
    end

    DriverPortal -. "Attempts /admin/*" .-> AccessDeniedD["Redirect to driver_portal.php"]
    Portal -. "Attempts /admin/*" .-> AccessDeniedA["Redirect to portal.php"]
    AdminDash --> Logout["Logout (logout.php)<br/>Destroy Session"]
    DriverPortal --> Logout
    Portal --> Logout
    Logout --> Login
```

## Role Access Enforcement Matrix
| Screen / Route | Guest | Attendee | Driver | Admin |
|---|---|---|---|---|
| `/login.php` | Allowed | Redirects to `portal.php` | Redirects to `driver_portal.php` | Redirects to `admin/dashboard.php` |
| `/portal.php` | Redirects to `login.php` | **Allowed (Read-Only)** | Redirects to `driver_portal.php` | Redirects to `admin/dashboard.php` |
| `/driver_portal.php` | Redirects to `login.php` | **Blocked** | **Allowed (Read-Only)** | **Blocked** |
| `/admin/*` (Dashboard, Attendees, Allocate, Drivers, Logistics, Reports) | Redirects to `login.php` | **Blocked** | **Blocked** | **Allowed** |
