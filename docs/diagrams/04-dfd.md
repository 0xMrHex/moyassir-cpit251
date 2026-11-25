# Data Flow Diagrams (DFD) — Level 0 & Level 1

## 1. Level 0 DFD (Context Diagram)
Shows the **Moyassir (ميسر) System** interacting with its three external entities: **Campaign Manager (Admin)**, **Bus Driver**, and **Attendee (Pilgrim)**.

```mermaid
flowchart LR
    Admin["External Entity:<br/>Campaign Manager (Admin)"]
    Driver["External Entity:<br/>Bus Driver"]
    Attendee["External Entity:<br/>Attendee (Pilgrim)"]
    System(("0.0<br/>Moyassir System<br/>(Hajj & Umrah Logistics)"))

    Admin -- "Credentials, Attendee CRUD,<br/>Driver Management & Service Status,<br/>Allocations, Schedules, Report Requests" --> System
    System -- "Dashboard KPIs, Capacity & Status Alerts,<br/>Filtered Lists, Printable Manifests & CSV" --> Admin

    Driver -- "Driver Login Credentials" --> System
    System -- "Assigned Bus Details, Service Status,<br/>Bus Schedule & Passenger Manifest" --> Driver

    Attendee -- "Attendee Login Credentials<br/>(National ID + Password)" --> System
    System -- "Read-Only Personal Card:<br/>Bus, Driver, Tent Zone & Live Itinerary" --> Attendee
```

---

## 2. Level 1 DFD (Detailed Functional Decomposition)

```mermaid
flowchart TD
    Admin["Campaign Manager (Admin)"]
    Driver["Bus Driver"]
    Attendee["Attendee (Pilgrim)"]

    P1(("1.0<br/>Authenticate<br/>User (RBAC)"))
    P2(("2.0<br/>Manage Attendees<br/>& Search/Filter"))
    P3(("3.0<br/>Allocate Bus & Tent<br/>(Cap & Status Check)"))
    P4(("4.0<br/>Manage Drivers,<br/>Buses & Itineraries"))
    P5(("5.0<br/>Generate Manifests<br/>& CSV Reports"))
    P6(("6.0<br/>Serve Attendee<br/>Portal"))
    P7(("7.0<br/>Serve Driver<br/>Schedule Portal"))

    D1[("D1: USERS")]
    D2[("D2: ATTENDEES")]
    D3[("D3: BUSES")]
    D4[("D4: TENTS")]
    D5[("D5: ITINERARIES")]
    D6[("D6: DRIVERS")]

    Admin --> P1
    Driver --> P1
    Attendee --> P1
    P1 <--> D1

    Admin --> P2
    P2 --> D1
    P2 <--> D2

    Admin --> P3
    P3 <--> D2
    P3 --> D3
    P3 --> D4

    Admin --> P4
    P4 --> D1
    P4 <--> D6
    P4 <--> D3
    P4 --> D4
    P4 --> D5

    Admin --> P5
    D2 --> P5
    D3 --> P5
    D4 --> P5
    D6 --> P5

    Attendee --> P6
    D2 --> P6
    D3 --> P6
    D4 --> P6
    D5 --> P6
    D6 --> P6

    Driver --> P7
    D6 --> P7
    D3 --> P7
    D5 --> P7
    D2 --> P7
```
